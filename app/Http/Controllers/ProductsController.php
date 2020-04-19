<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Category;
use App\Exceptions\InvalidRequestException;
use Illuminate\Pagination\LengthAwarePaginator;
use App\SearchBuilders\ProductSearchBuilder;
use App\Services\ProductService;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = 16;

        $builder = (new ProductSearchBuilder())->onSale()->paginate($perPage, $page);

        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            $builder->category($category);
        }

        if ($search = $request->input('search', '')) {
            $keywords = array_filter(explode(' ', $search));
            $builder->keywords($keywords);
        }

        if ($search || isset($category)) {
            $builder->aggregateProperties();
        }

        $propertyFilters = [];
        if ($filterString = $request->input('filters')) {
            $filterArray = explode('|', $filterString);
            foreach ($filterArray as $filter) {
                list($name, $value) = explode(':', $filter);
                $propertyFilters[$name] = $value;
                $builder->propertyFilter($name, $value);
            }
        }

        if ($order = $request->input('order', '')) {
            if (preg_match('/^(.+)_(asc|desc)/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }

        $result = app('es')->search($builder->getParams());

        // 从聚合查询出的结果里取得属性
        $properties = [];
        if (isset($result['aggregations'])) {
            $properties = collect($result['aggregations']['diyiceng']['dierceng']['buckets'])->map(function ($bucket) {
                return [
                    'key'       => $bucket['key'],
                    'values'    => collect($bucket['disanceng']['buckets'])->pluck('key')->all(),
                ];
            })
            ->filter(function ($property) use ($propertyFilters) {
                return count($property['values']) > 1 && !isset($propertyFilters[$property['key']]);
            });
        }

        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();
        $products = Product::query()->byIds($productIds)->get();

        // 数据 / 总数 / 每页数量 / 当前页码 / 这里只有链接，参数另外处理的
        $pager = new LengthAwarePaginator($products, $result['hits']['total']['value'], $perPage, $page, [
            'path'  => route('products.index', false),
        ]);

        return view('products.index', [
            'products' => $pager,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
            'category' => $category ?? null,
            'properties'    => $properties,
            'propertyFilters' => $propertyFilters
        ]);
    }

    public function show(Product $product, Request $request, ProductService $service)
    {
        if (!$product->on_sale) {
            throw new InvalidRequestException('商品未上架');
        }

        $favored = false;
        if ($user = $request->user()) {
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        $reviews = OrderItem::query()
                ->with(['order.user', 'productSku'])
                ->whereNotNull('reviewed_at')
                ->orderBy('reviewed_at', 'desc')
                ->limit(10)
                ->get();

        $builder = (new ProductSearchBuilder())->onSale()->paginate(4, 1);
        foreach ($product->properties as $property) {
            $builder->propertyFilter($property->name, $property->value, 'should');
        }

        $similarProductIds = $service->getSimilarProductIds($product, 4);
        $similarProducts = Product::query()->byIds($similarProductIds)->get();

        return view('products.show', [
            'product' => $product,
            'favored' => $favored,
            'reviews' => $reviews,
            'similar' => $similarProducts,
        ]);
    }

    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);
        $count = $request->user()->favoriteProducts()->count();
        return view('products.favorites', ['products' => $products]);
    }

    public function favor(Product $product, Request $request)
    {
        $user = $request->user();
        if ($user->favoriteProducts()->find($product->id)) {
            return [];
        }
        $user->favoriteProducts()->attach($product);
        return [];
    }

    public function disfavor(Product $product, Request $request)
    {
        $user = $request->user();
        $user->favoriteProducts()->detach($product);
        return [];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Category;
use App\Exceptions\InvalidRequestException;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = 16;

        // 准备
        $params = [
            'index' => 'products',
            // 'type'  => '_doc',
            'body'  => [
                'from'  => ($page - 1) * $perPage,
                'size'  => $perPage,
                'query' => [
                    'bool'  => [
                        'filter'    => [
                            ['term' => ['on_sale' => true]],
                        ],
                    ],
                ],
            ],
        ];

        // 分类，直接找分类 id 或者找下级分类
        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            if ($category->is_directory) {
                $params['body']['query']['bool']['filter'][] = [
                    'prefix' => ['category_path' => $category->path . $category->id . '-'],
                ];
            } else {
                $params['body']['query']['bool']['filter'][] = [
                    'term' => ['category_id' => $category->id],
                ];
            }
        }

        // 排序
        if ($order = $request->input('order', '')) {
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    $params['body']['sort'] = [[$m[1] => $m[2]]];
                }
            }
        }

        // 搜索
        if ($search = $request->input('search', '')) {
            $keywords = array_filter(explode(' ', $search));
            $params['body']['query']['bool']['must'] = [];
            foreach ($keywords as $keyword) {
                $params['body']['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query' => $keyword,
                        'fields' => [
                            'title^2',
                            'long_title^2',
                            'category^2',
                            'description',
                            'skus_title',
                            'skus_description',
                            'properties_value',
                        ],
                    ],
                ];
            }
        }

        // 属性筛选
        $propertyFilters = [];
        if ($filterString = $request->input('filters')) {
            $filterArray = explode('|', $filterString);
            foreach ($filterArray as $filter) {
                list($name, $value) = explode(':', $filter);
                $propertyFilters[$name] = $value;
                $params['body']['query']['bool']['filter'][] = [
                    'nested' => [
                        'path' => 'properties',
                        'query' => [
                            ['term' => ['properties.search_value' => $filter]],
                            // ['term' => ['properties.name' => $name]],
                            // ['term' => ['properties.value' => $value]],
                        ],
                    ],
                ];
            }
        }

        // 得到包含的属性们
        if ($search || isset($category)) {
            $params['body']['aggs'] = [
                'juheshuxing1' => [
                    'nested' => [
                        'path' => 'properties',
                    ],
                    'aggs' => [
                        'juheshuxing2' => [
                            'terms' => [
                                'field' => 'properties.name',
                            ],
                            'aggs' => [
                                'juheshuxing3' => [
                                    'terms' => [
                                        'field' => 'properties.value',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
// echo '<pre>';
// var_dump($params);
// exit;
        $result = app('es')->search($params);

        // 从聚合查询出的结果里取得属性
        $properties = [];
        if (isset($result['aggregations'])) {
            $properties = collect($result['aggregations']['juheshuxing1']['juheshuxing2']['buckets'])->map(function ($bucket) {
                return [
                    'key'       => $bucket['key'],
                    'values'    => collect($bucket['juheshuxing3']['buckets'])->pluck('key')->all(),
                ];
            })
            ->filter(function ($property) use ($propertyFilters) {
                return count($property['values']) > 1 && !isset($propertyFilters[$property['key']]);
            });
        }

        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderByRaw(sprintf("find_in_set(id, '%s')", join(',', $productIds)))
            ->get();
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

    public function show(Product $product, Request $request)
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

        return view('products.show', ['product' => $product, 'favored' => $favored, 'reviews' => $reviews]);
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

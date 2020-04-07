<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

class CategoriesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '商品分类';

    public function edit($id, Content $content)
    {
        return $content
                ->title($this->title())
                ->description($this->description['edit'] ?? trans('admin.edit'))
                ->body($this->form(true)->edit($id));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Category);

        $grid->model()->orderBy('path');
        $grid->id('ID')->sortable();
        $grid->name('名称');
        $grid->level('层级');
        $grid->is_directory('是否目录')->display(function ($value) {
            return $value ? '是' : '否';
        });
        $grid->path('路径');
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($isEditing = false)
    {
        $form = new Form(new Category);

        $form->text('name', '名称')->rules('required');

        if ($isEditing) {
            $form->display('is_directory', '是否目录')->with(function ($value) {
                return $value ? '是' : '否';
            });
            $form->display('parent.name', '父类');
        } else {
            $form->radio('is_directory', '是否目录')
                ->options(['1' => '是', '0' => '否'])
                ->default('0')
                ->rules('required');
            $form->select('parent_id', '父类')->ajax('/admin/api/categories');
        }

        return $form;
    }

    public function apiIndex(Request $request)
    {
        $search = $request->input('q');
        $result = Category::query()
            ->where('is_directory', boolval($request->input('is_directory', true)))
            ->where('name', 'like', "%{$search}%")
            ->paginate();

        $result->setCollection($result->getCollection()->map(function (Category $category) {
            return ['id' => $category->id, 'text' => $category->full_name];
        }));
        return $result;
    }
}

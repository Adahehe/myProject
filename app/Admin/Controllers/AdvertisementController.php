<?php

namespace App\Admin\Controllers;

use App\Model\Advertisement;
use App\Http\Controllers\Controller;
use App\Model\Product;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class AdvertisementController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('网站管理')
            ->description('广告')
            ->breadcrumb(['text' => '广告'])
            ->body($this->grid());
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('广告')
            ->description('编辑')
            ->breadcrumb(['text' => '广告'])
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('广告')
            ->description('创建')
            ->breadcrumb(['text' => '广告'])
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Advertisement);

        $grid->model()->orderBy('is_show','desc');
        $grid->ad_id('id');
        $grid->ad_img('图片')->gallery(['width' => 50, 'height' => 50]);
        $grid->used('图片使用位置')->using(Advertisement::advertisementUsed());
        $states = [
            'on'  => ['value' => 1, 'text' => '是', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
        ];
        $grid->is_show('是否显示')->switch($states);
        $grid->sort('排序')->editable('text');
        $grid->dec('描述');
        $grid->created_at('创建时间');
        $grid->updated_at('修改时间');

        //删除多余的按钮
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableExport();

        //搜索功能
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->equal('used', '图片使用位置')->select(Advertisement::advertisementUsed());
        });

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Advertisement);
        $form->image('ad_img' , '上传图片')->move('advertisement/images')
            ->uniqueName()->removable();
//        $form->uploadImg('ad_img' , '上传图片');
        $form->text('dec', '图片描述');
        $states = [
            'on'  => ['value' => 1, 'text' => '是', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
        ];
        $form->switch('is_show', '是否显示')->states($states);
        $form->text('sort', '排序')->help('数字越大排序越后');
        $form->select('used', '显示的位置')->options(Advertisement::advertisementUsed());
        $form->url('http' , '图片链接')->help('如果图片与链接  无关，此项可以为空');
        $form->select('product_id' , '对应商品')->options(Product::findProductNameById())->help('如果图片与商品无关，此项可以为空');
        //去掉脚部多余的按钮
        $form->footer(function ($footer) {
            $footer->disableReset();
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        return $form;
    }
}

<?php

namespace App\Admin\Controllers;

use App\Model\BusinessAddress;
use App\Http\Controllers\Controller;
use App\Model\Regions;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use http\Env\Request;

class BusinessAddressController extends Controller
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
            ->header('Index')
            ->description('description')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
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
            ->header('Edit')
            ->description('description')
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
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BusinessAddress);

        $grid->business_address_id('ID');
        $grid->county('所属地区')->display(function ($id) {
            return Regions::findAddress($id);
        });
        $grid->business_name('发货人');
        $states = [
            'on'  => ['value' => 1, 'text' => '是', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'default'],
        ];
        $grid->status('默认地址')->switch($states);

        $grid->created_at('创建时间');
        $grid->updated_at('更新时间');





        //待处理数据
//        $grid->store_id('Store id');
//        $grid->deleted_at('Deleted at');
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(BusinessAddress::findOrFail($id));

        $show->business_address_id('ID');
        $show->business_name('发货人');
//        $show->province('Province');
//        $show->city('City');
        $show->county('所属地区')->as(function ($id) {
            return Regions::findAddress($id);
        });
        $show->address('详细地址');

//        $show->store_id('Store id');
        $show->created_at('创建时间');
        $show->updated_at('更新时间');
//        $show->deleted_at('Deleted at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BusinessAddress);

        $form->text('business_name', '发货人');
        $form->select('province', '省')->options(Regions::province())->load('city', '/admin/api/getregion');
        $form->select('city', '市')->load('county', '/admin/api/getregion');
        $form->select('county', '县/区');
        $form->text('address', '详细地址');
        $form->gaodemap('gps' , '地图');
        $states = [
            'on'  => ['value' => 1, 'text' => '是', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
        ];

        $form->switch('status', '设为默认地址')->states($states);
//        $form->number('store_id', 'Store id');

        //去掉脚部按钮
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        return $form;
    }
}
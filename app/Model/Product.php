<?php

namespace App\Model;

class Product extends Common
{
    //
    protected $table = 'product';
    protected $primaryKey = 'product_id';

    protected $casts = [
        'about_product' => 'json',
    ];

    const DISPLAY = 1; //显示
    const UN_DISPLAY = 0; //不显示

    const HOT_PRODUCT = 1; //热门商品
    const NEW_PRODUCT = 2; //最新商品
    const RECOMMEND_PRODUCT = 3 ; //推荐商品
    const SS_PRODUCT = 4; //特价商品
    const ORDINARY_PRODUCT = 5 ; //普通商品


    public static function productStatic()
    {
        return [
            self::HOT_PRODUCT => '热门',
            self::NEW_PRODUCT => '最新',
            self::RECOMMEND_PRODUCT => '推荐',
            self::SS_PRODUCT => '特价',
            self::ORDINARY_PRODUCT => '普通',
        ];
    }

    //多图上传
    public function setProductMasterImgAttribute($pictures)
    {
        if (is_array($pictures)) {
            $this->attributes['product_master_img'] = json_encode($pictures);
        }
    }
    public function getProductMasterImgAttribute($pictures)
    {
        return json_decode($pictures, true);
    }
    public static function findProductNameById()
    {
        return Product::where('is_show' , self::DISPLAY)->pluck('product_name' , 'product_id')->toArray();
    }
    public static function findProductById($id)
    {
        return Product::where('product_id' , $id)->first()->toArray();
    }

    /**
     * 更具url的id查找商品 乱序显示 查找所有商品时带分页
     * @param $id
     * @param int $limit
     * @return mixed
     */
    public static function findProductUrlByTopId($id , $limit = 0)
    {
        //todo 优化查询 多带点查询条件
        //查出黑名单的商家
        $blacklist_store = Store::findBlackListStoreId();
        if($limit){
            $product =  Product::where([['category_top_id' , $id] , ['is_show' , self::DISPLAY]])
                ->whereNotIn('store_id',$blacklist_store)
                ->limit($limit)->get()->toArray();
//            shuffle($product);
        }else{
            //一页显示20个商品
            $product =  Product::where([['category_top_id' , $id] , ['is_show' , self::DISPLAY]])
                ->whereNotIn('store_id',$blacklist_store)
                ->paginate(20);
        }
        //todo 乱序显示商品
        return $product;
    }
    public static function findProductUrlById($id)
    {
        //一页显示20个商品
        $blacklist_store = Store::findBlackListStoreId();
        $product =  Product::where([['category_id' , $id] , ['is_show' , self::DISPLAY]])
            ->whereNotIn('store_id',$blacklist_store)
            ->paginate(20);
        //todo 乱序显示商品
        return $product;

    }
    //查找销量前五的商品 同类型的
    public static function findProductTypeSalesVolumeTop($id , $limit=0)
    {
        $blacklist_store = Store::findBlackListStoreId();
        $category_id = Product::where('product_id' , $id)->value('category_id');
        $topProduct = Product::where([['category_id' , $category_id] , ['is_show' , self::DISPLAY]])
            ->whereNotIn('store_id',$blacklist_store)
            ->orderBy('sales_volume' , 'desc')->limit($limit)->get()->toArray();
        return $topProduct;
    }

    /**
     * 立即购买商品订单查看
     * @param $product_id
     * @param int $num
     * @return array
     */
    public static function buyProductNow($product_id , $num = 2)
    {
        $arr = [];
        $brr = [];
        $product = Product::where('product_id' , $product_id)->first()->toArray();
        $store_id = Store::where('store_id' , $product['store_id'])->first()->toArray();
        $arr['store_id'] = $store_id;
        $brr['num'] = $num;
        $brr['product'] = $product;
        $brr['product']['cost'] = $product['present_price']*$num;
        $arr['product'][] = $brr;
        $order[] = $arr;
        return $order;
    }

    /**
     * 活动特价页面
     * @return mixed
     */
    public static function todayDeal($type)
    {
        $product = Product::where('status' , $type)->get()->toArray();
        return $product;
    }

    /**
     * 最新上传得10个商品
     * @param $id
     * @param int $limit
     * @return mixed
     */
    public static function findProductTypeSalesVolumeNew($id , $limit=0)
    {
        $blacklist_store = Store::findBlackListStoreId();
        $topProduct = Product::where([['category_top_id' , $id] , ['is_show' , self::DISPLAY]])
            ->whereNotIn('store_id',$blacklist_store)
            ->orderBy('product_id' , 'desc')->limit($limit)->get()->toArray();
        return $topProduct;
    }

    /**
     * 搜索商品
     * @param $search
     * @return mixed
     */
    public static function searchProduct($search)
    {
        $blacklist_store = Store::findBlackListStoreId();
        $product =  Product::where([['is_show' , self::DISPLAY],['keyword','like','%'.$search.'%']])
            ->orWhere([['is_show' , self::DISPLAY],['product_name','like','%'.$search.'%']])
            ->whereNotIn('store_id',$blacklist_store)
            ->paginate(20);
        return $product;
    }


    //一对一关联关系  广告表
    public function advertisement(){
        return $this->belongsTo(Advertisement::class, 'product_id', 'product_id');
    }
}

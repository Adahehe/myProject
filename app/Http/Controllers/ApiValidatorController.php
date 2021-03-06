<?php

namespace App\Http\Controllers;

use App\Model\Advertisement;
use App\Model\Member;
use App\Model\Message;
use App\Model\Product;
use App\Model\Store;
use App\Model\StoreLog;
use App\Model\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiValidatorController extends Controller
{
    //商家申请 (包括创建申请和更新)
    public function addApply(Request $request)
    {
        //获取提交数据
        $business_nickname = $request->post('business_nickname');
        $store_name = $request->post('store_name');
        $address = $request->post('address');
        $business_name = $request->post('business_name');
        $business_tel = $request->post('business_tel');
        $identity_card = $request->post('identity_card');
        $post_num = $request->post('post_num');
        $business_pic = $request->post('adminImg');
//        $apply_id = $request->post('apply_id')?$request->post('apply_id'):mt_rand(100000000,199999999);
        $store_id = $request->post('store_id');

        $credentials = $request->all();
//        dd($credentials);
        //表单验证
        $rules = [
            'business_nickname'=>'required',
            'store_name'=>'required',
            'address'=>'required',
            'business_name'=>'required',
            'identity_card'=>'required',
            'post_num'=>'required|numeric',
            'adminImg'=>'required',
        ];
        $messages = [
            'business_nickname.required' => '昵称不能为空',
            'store_name.required' => '店名不能为空',
            'address.required' => '地址不能为空',
            'business_name.required' => '实名不能为空',
            'identity_card.required' => '身份证不能为空',
            'post_num.required' => '邮编不能为空',
            'business_tel.required' => '手机号不能为空',
            'adminImg.required' => '请上传实名照片！',
            'numeric' => '填写的格式错误',
            'unique' => '手机号被使用',
        ];

       //验证通过 将数据写入数据 如果有 手机号码是创建的
        if($business_tel){
            $rules['business_tel'] = 'required|numeric|unique:store';
            Validator::make($credentials , $rules , $messages)->validate();//验证
            $store = new Store;
            $store->business_tel = $business_tel;
        }else{
            Validator::make($credentials , $rules , $messages)->validate();//验证
            $store = Store::find($store_id);
        }
        $store->business_nickname = $business_nickname;
        $store->store_name = $store_name;
        $store->address = $address;
        $store->business_name = $business_name;

        $store->identity_card = $identity_card;
        $store->post_num = $post_num;
        $store->business_pic = json_encode($business_pic);
        $store->status = Store::PENDING_APPLICATION;
        $store->blacklist = 0;
//        $store->apply_id = $apply_id;
        $store->save();

        //写入申请日志
        $store_log = new StoreLog;
        $store_log->store_form_id = $store->store_id;
        $store_log->action_status = Store::PENDING_APPLICATION;
        $store_log->save();

        //商家修改提交资料 修改日志 上锁 禁止修改
        if(!$business_tel){
            $store_log_id = StoreLog::where([['store_form_id',$store->store_id],['action_status' , Store::APPLICATION_FAILED]])->orderBy('id','decs')->first();
            $store_log_up = StoreLog::find($store_log_id->id);
            $store_log_up->lock = 1;
            $store_log_up->save();
        }

        //成功后 返回查询页面
        return redirect('/applySuccess');
    }

    /**
     * 修改密码api
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postReset(Request $request)
    {
        $oldPassword = $request->input('oldpassword');
        $password = $request->input('password');
        $data = $request->all();

        $rules = [
            'oldpassword'=>'required|between:6,20',
            'password'=>'required|between:6,20|confirmed',
        ];
        $messages = [
            'oldpassword.required' => '原密码不能为空',
            'password.required' => '新密码不能为空',
            'between' => '密码必须是6~20位之间',
            'password.confirmed' => '新密码和确认密码不匹配'
        ];
        $validator = Validator::make($data, $rules , $messages);
        $user = Auth::user();
        $validator->after(function($validator) use ($oldPassword, $user) {
            if (!Hash::check($oldPassword, $user->password)) {
                $validator->errors()->add('oldpassword', '原密码错误');
            }
        });
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $user->password = bcrypt($password);
        $user->save();
        return redirect( '/userInfo');
    }

    /**
     * 发布求购信息
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function upTopic(Request $request)
    {
        $topic_dec = $request->post('topic_dec');
        $used_id = $request->post('used_id');
        $topic_type = $request->post('topic_type');
        $status = Topic::UP_TOPIC;
        $credentials = $request->all();
        $rules = [
            'topic_dec'=>'required',
            'topic_type'=>'required',
        ];
        $messages = [
            'topic_dec.required' => '内容不能为空',
            'topic_type.required' => '请选择类型',
        ];
        Validator::make($credentials , $rules , $messages)->validate();//验证
        $member = Member::where('users_id' , $used_id)->first();
        $topic = new Topic;
        $topic->user_id = $used_id;
        $topic->topic_dec = $topic_dec;
        $topic->topic_follow = 0;
        $topic->topic_type = $topic_type;
        $topic->status = $status;
        $topic->user_pic = isset($member->member_pic)?$member->member_pic:null;
        $topic->save();
        return redirect( '/show_topic_list');
    }

    /**
     * 留言
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function upMessage(Request $request)
    {
        $message_text = $request->post('message');
        $topic_id = $request->post('topic_id');
        $user_id = $request->post('user_id');
        $credentials = $request->all();
        $rules = [
            'message'=>'required',
        ];
        $messages = [
            'message.required' => '内容不能为空',
        ];
        Validator::make($credentials , $rules , $messages)->validate();//验证
        $message = new Message;
        $message->user_id = $user_id;
        $message->topic_id = $topic_id;
        $message->message = $message_text;
        $message->save();

        $topic = Topic::find($topic_id);
        $topic->topic_follow = (int)$topic->topic_follow + 1;
        $topic->save();

        return redirect( '/show_topic_detail/'.$topic_id);
    }

    /**
     * 回复留言
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function upReply(Request $request)
    {
        $reply = $request->post('reply');
        $message_id = $request->post('message_id');
        $topic_id = $request->post('topic_id');
        $credentials = $request->all();
        $rules = [
            'reply'=>'required',
        ];
        $messages = [
            'reply.required' => '回复的内容不能为空',
        ];
        Validator::make($credentials , $rules , $messages)->validate();//验证
        $message = Message::find($message_id);
        $message->reply = $reply;
        $message->save();

        return redirect( '/show_topic_detail/'.$topic_id);
    }
}
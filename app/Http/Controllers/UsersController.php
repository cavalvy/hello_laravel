<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mail;

class UsersController extends Controller
{

    public function __construct()
    {
        //除了指定两个页面不用登录访问外，其他页面未登录访问回重定向到登录页
        $this->middleware('auth',[
            'except' => ['index','show','create','store','confirmEmail']
        ]);
        //只让未登录的用户访问的页面
        $this->middleware('guest',[
            'only' => ['create']
        ]);
    }

    public function index()
    {
        $users = User::paginate(6);
        return view('users.index',compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
        return view('users.show',compact('user'));
    }

    /**
     * 注册
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',//正对数据库表users
            'password' => 'required|confirmed|min:6'//confirmed校验两次输入密码一致性
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        $this->sendEmailConfirmationTo($user);
        session()->flash('success','验证邮件已发送到你的注册邮箱上，请注意查收。');

        return redirect('/');
    }

    public function edit(User $user)
    {
//        try{
            $this->authorize('update',$user);

            return view('users.edit',compact('user'));
//        }catch(AccessDeniedHttpException $e){
//            if(config('app.debug')) {
//                session()->flash('danger', '无权访问：' . $e->getMessage());
//            } else {
//                session()->flash('danger', '无权访问');
//            }
//        }
    }

    /**
     * 编辑用户
     * @param User $user
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(User $user,Request $request)
    {
        $this->validate($request,[
            'name' => 'required|max:50',
            'password' => 'required|confirmed|min:6'
        ]);
        $this->authorize('update',$user);

        $data = [];
        $data['name'] = $request->name;
        if($request->password){
            $data['password'] = bcrypt($request->password);
        }
        $user->update([
            'name' => $request->name,
            'password' => bcrypt($request->password)
        ]);
        session()->flash('success','个人资料更新成功！');
        return redirect()->route('users.show',$user->id);
    }

    public function destroy(User $user)
    {
        $this->authorize('destroy',$user);
        $user->delete();
        session()->flash('success','删除用户成功');
        return back();
    }

    /**
     * 发送邮件
     * @param $user
     */
    public function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        $to = $user->email;
        $subject = '感谢注册 Sample 应用！请确认你的邮箱。';

        Mail::send($view,$data,function ($message) use ($to,$subject){
            $message->to($to)->subject($subject);
        });
    }

    /**
     * 点击右键激活码后调用该方法
     * @param $token
     */
    public function confirmEmail($token)
    {
        $user = User::where('activation_token',$token)->firstOrFail();
        $user->activation = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success','激活成功！');
        redirect()->route('users.show',[$user]);
    }
}

<?php

return [

    // 站点标题
    'name' => '你看你🐴呢',

    // 页面顶部 Logo
    'logo' => '<b>🐴</b>',

    // 页面顶部小 Logo
    'logo-mini' => '<b>🐎</b>',

    // Laravel-Admin 启动文件路径
    'bootstrap' => app_path('Admin/bootstrap.php'),

    // 路由配置
    'route' => [
        // 路由前缀
        'prefix' => env('ADMIN_ROUTE_PREFIX', 'admin'),
        // 控制器命名空间前缀
        'namespace' => 'App\\Admin\\Controllers',
        // 默认中间件列表
        'middleware' => ['web', 'admin'],
    ],

    // Laravel-Admin 的安装目录
    'directory' => app_path('Admin'),

    // Laravel-Admin 页面标题
    'title' => '阿多米尼斯多雷特',

    // 是否使用 https
    'https' => env('ADMIN_HTTPS', false),

    // 用户认证设置
    'auth' => [

        'controller' => App\Admin\Controllers\AuthController::class,

        'guard' => 'admin',

        'guards' => [
            'admin' => [
                'driver'   => 'session',
                'provider' => 'admin',
            ],
        ],

        'providers' => [
            'admin' => [
                'driver' => 'eloquent',
                'model'  => Encore\Admin\Auth\Database\Administrator::class,
            ],
        ],

        // 是否展示 “保持登录” 选项
        'remember' => true,

        // 登录页面 URL
        'redirect_to' => 'auth/login',

        // 无需用户认证即可访问的地址
        'excepts' => [
            'auth/login',
            'auth/logout',
        ],
    ],

    // 文件上传设置
    'upload' => [

        // 对应 config/filesystem.php 里的 disks
        'disk' => 'admin',

        // Image and file upload path under the disk above.
        'directory' => [
            'image' => 'images',
            'file'  => 'files',
        ],
    ],

    // 数据库设置
    'database' => [

        // Database connection for following tables.
        'connection' => '',

        // User tables and model.
        'users_table' => 'admin_users',
        'users_model' => Encore\Admin\Auth\Database\Administrator::class,

        // Role table and model.
        'roles_table' => 'admin_roles',
        'roles_model' => Encore\Admin\Auth\Database\Role::class,

        // Permission table and model.
        'permissions_table' => 'admin_permissions',
        'permissions_model' => Encore\Admin\Auth\Database\Permission::class,

        // Menu table and model.
        'menu_table' => 'admin_menu',
        'menu_model' => Encore\Admin\Auth\Database\Menu::class,

        // Pivot table for table above.
        // 日志和关联
        'operation_log_table'    => 'admin_operation_log',
        'user_permissions_table' => 'admin_user_permissions',
        'role_users_table'       => 'admin_role_users',
        'role_permissions_table' => 'admin_role_permissions',
        'role_menu_table'        => 'admin_role_menu',
    ],

    // 日志设置
    'operation_log' => [

        'enable' => true,

        // 只记录以下类型的请求
        'allowed_methods' => ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'],

        // 不记录的路由
        'except' => [
            'admin/auth/logs*',
        ],
    ],

    // 路由是否检查权限
    'check_route_permission' => true,

    // 菜单是否检查权限
    'check_menu_roles'       => true,

    // 管理员默认头像
    'default_avatar' => '/vendor/laravel-admin/AdminLTE/dist/img/usr2-160x160.jpg',

    // 地图组件提供商
    'map_provider' => 'google',

    // 皮肤
    'skin' => 'skin-blue-light',

    /*
    |--------------------------------------------------------------------------
    | Application layout
    |--------------------------------------------------------------------------
    |
    | This value is the layout of admin pages.
    | @see https://adminlte.io/docs/2.4/layout
    |
    | Supported: "fixed", "layout-boxed", "layout-top-nav", "sidebar-collapse",
    | "sidebar-mini".
    |
    */
    'layout' => ['sidebar-mini', 'sidebar-collapse'],

    // 登录页背景图
    'login_background_image' => '',

    // 显示版本
    'show_version' => true,

    // 显示环境
    'show_environment' => true,

    // 菜单绑定权限
    'menu_bind_permission' => true,

    // 默认启用面包屑
    'enable_default_breadcrumb' => true,

    // 压缩资源文件
    'minify_assets' => [

        // 不要压缩的资源
        'excepts' => [

        ],

    ],

    // 启用菜单搜索
    'enable_menu_search' => true,

    // 顶部警告信息
    'top_alert' => '',

    // 表格操作展示样式
    'grid_action_class' => \Encore\Admin\Grid\Displayers\DropdownActions::class,

    // 扩展所在目录
    'extension_dir' => app_path('Admin/Extensions'),

    // 扩展设置
    'extensions' => [

    ],
];

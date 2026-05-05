# 友情链接管理器插件

[![License](https://img.shields.io/badge/license-GPL--3.0-orange)](https://opensource.org/licenses/GPL-3.0)
[![WordPress Version](https://img.shields.io/badge/WordPress-5.0%2B-blue)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.0%2B-purple)](https://php.net/)

**友情链接管理器插件** 是一款专为 WordPress 网站设计的插件，旨在帮助站长轻松管理友情链接申请和审核。无论是个人博客、企业网站还是社区论坛，都可以通过这款插件提升友情链接的管理效率，增强网站互动性。

---

## 功能特性

- **前台友链申请表单**：用户可通过前台表单提交友链申请，支持填写网站名称、URL、Logo、邮箱和描述。
- **友链展示短代码**：使用 `[friend_links]` 短代码在前台展示已通过的友链，支持卡片式网格布局。
- **后台集中管理**：站长可在 WordPress 后台集中管理所有友链申请，支持分页、状态筛选和排序。
- **批量操作**：支持批量通过、拒绝和删除友链申请。
- **自动同步**：审核通过的链接自动同步到 WordPress 默认的链接管理器。
- **CSRF 防护**：后台所有操作均带 nonce 验证，前台表单亦有 CSRF 保护。
- **频率限制**：同一 IP 60秒内仅允许提交一次申请，防止恶意刷量。
- **蜜罐反垃圾**：表单内置隐藏蜜罐字段，自动拦截机器人提交。
- **输入长度校验**：前后端均有输入长度限制，防止超长数据入库。
- **简洁易用**：开箱即用，无需复杂配置。

---

## 安装指南

### 方法 1：通过 WordPress 后台安装

1. 登录 WordPress 后台。
2. 进入 **插件 > 安装插件**。
3. 搜索 **友情链接管理器**，点击 **安装** 并 **激活**。

### 方法 2：手动安装

1. 下载插件 ZIP 文件。
2. 解压文件，将文件夹上传到 `wp-content/plugins/` 目录。
3. 登录 WordPress 后台，进入 **插件 > 已安装插件**。
4. 找到 **友情链接管理器**，点击 **激活**。

---

## 使用教程

### 1. 友链申请表单

编辑页面或文章，插入短代码：
```plaintext
[friend_links_form]
```

激活插件后会自动创建"友情链接申请"页面，访问 `https://你的域名/friend-links-apply/` 即可。

### 2. 友链展示

在任意页面或文章中插入短代码：
```plaintext
[friend_links]
```

支持的参数：
| 参数 | 说明 | 默认值 |
|------|------|--------|
| `orderby` | 排序字段：`id`、`name`、`created_at` | `id` |
| `order` | 排序方向：`ASC`、`DESC` | `DESC` |
| `count` | 显示数量，0 为全部 | `0` |

示例：
```plaintext
[friend_links orderby="name" order="ASC" count="20"]
```

### 3. 后台管理

1. 登录 WordPress 后台，点击左侧菜单 **友情链接**。
2. 可按状态筛选（待审核 / 已通过 / 已拒绝）、排序、分页浏览。
3. 支持单条操作（通过、拒绝、删除）和勾选后的批量操作。

---

## 升级说明

插件从 1.x 升级到 2.0 时，数据库表会自动新增 `logo_url`、`email`、`created_at` 字段，已有数据不受影响。升级由 `plugins_loaded` 钩子自动处理，无需手动操作。

---

## 常见问题

### Q1：如何修改友情链接申请页面的 URL？
- 进入 **页面 > 所有页面**，找到 **友情链接申请**，修改别名（slug）即可。

### Q2：如何自定义表单样式？
- 编辑 `assets/css/style.css` 文件自定义样式。

### Q3：如何防止用户重复提交？
- 插件自动检测重复 URL，同时限制同一 IP 60秒内只能提交一次。

### Q4：如何删除插件？
1. 进入 **插件 > 已安装插件**，停用并删除插件。
2. 插件删除后，数据库表和友情链接申请页面会被自动清除。

---

## 贡献指南

1. Fork 本项目。
2. 创建新分支 (`git checkout -b feature/YourFeatureName`)。
3. 提交更改 (`git commit -m 'Add some feature'`)。
4. 推送到分支 (`git push origin feature/YourFeatureName`)。
5. 提交 Pull Request。

---

## 许可证

本项目采用 [GPL-3.0](https://opensource.org/licenses/GPL-3.0) 许可证。

---

## 联系我们

- 邮箱：motaoxx@outlook.com
- 官网：[https://imotao.com](https://imotao.com)
- GitHub：[https://github.com/motao123/friend-links-manage](https://github.com/motao123/friend-links-manage)

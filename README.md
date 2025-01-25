# 友情链接管理器插件

[![License](https://img.shields.io/badge/license-GPL--3.0-orange)](https://opensource.org/licenses/GPL-3.0)
[![WordPress Version](https://img.shields.io/badge/WordPress-5.0%2B-blue)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.0%2B-purple)](https://php.net/)

**友情链接管理器插件** 是一款专为 WordPress 网站设计的插件，旨在帮助站长轻松管理友情链接申请和审核。无论是个人博客、企业网站还是社区论坛，都可以通过这款插件提升友情链接的管理效率，增强网站互动性。

---

## 功能特性

- **前台友情链接申请表单**：用户可以通过前台表单提交友情链接申请。
- **后台集中管理**：站长可以在 WordPress 后台集中管理所有提交的友情链接申请。
- **自动同步到 WordPress 链接管理器**：审核通过的链接会自动同步到 WordPress 默认的链接管理器。
- **防止重复提交**：插件会自动检测重复的 URL，避免用户重复提交相同的链接。
- **安全可靠**：支持 CSRF 保护，防止跨站请求伪造攻击。
- **简洁易用**：插件界面简洁，操作简单，无需复杂配置，开箱即用。

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

### 1. 通过短代码嵌入友情链接申请表单

1. 编辑你想要显示表单的页面或文章。
2. 在内容中输入以下短代码：
   ```plaintext
   [friend_links_form]
   ```
3. 更新或发布页面。

### 2. 通过单独页面提交友情链接申请

1. 访问以下 URL：
   ```
   https://imotao.com/friend-links-apply/
   ```
   （将 `imotao.com` 替换为你的网站域名）
2. 填写表单并提交。

### 3. 后台管理友情链接

1. 登录 WordPress 后台。
2. 在左侧菜单中，点击 **友情链接**。
3. 审核、拒绝或删除友情链接申请。

---

## 插件截图

### 前台申请表单
![c237ce1e716136b33cbc280ab1a4a512_1737817731-image](https://github.com/user-attachments/assets/fbcb39f0-a638-40c8-8d6d-6cfa6bb74c3a)


### 后台管理页面
![1dda2bdedae5867270a55f0e66d6f264_1737818038-image](https://github.com/user-attachments/assets/56aeafb9-e91b-4c1d-8a26-a363355177ab)


---

## 常见问题

### Q1：如何修改友情链接申请页面的 URL？
- 进入 **页面 > 所有页面**，找到名为 **友情链接申请** 的页面。
- 点击 **快速编辑**，修改 **别名**（slug）即可。

### Q2：如何自定义表单样式？
- 编辑插件目录中的 `assets/css/style.css` 文件，自定义表单样式。

### Q3：如何防止用户重复提交？
- 插件会自动检测重复的 URL。如果 URL 已存在，用户会看到错误消息：“该URL已经提交过了，请勿重复提交！”

### Q4：如何删除插件？
1. 进入 **插件 > 已安装插件**。
2. 找到 **友情链接管理器**，点击 **停用** 并 **删除**。
3. 插件删除后，数据库表和友情链接申请页面也会被自动删除。

---

## 贡献指南

欢迎贡献代码！请遵循以下步骤：

1. Fork 本项目。
2. 创建新的分支 (`git checkout -b feature/YourFeatureName`)。
3. 提交更改 (`git commit -m 'Add some feature'`)。
4. 推送到分支 (`git push origin feature/YourFeatureName`)。
5. 提交 Pull Request。

---

## 许可证

本项目采用 [GPL-3.0](https://opensource.org/licenses/GPL-3.0) 许可证。

---

## 联系我们

如果你有任何问题或建议，欢迎通过以下方式联系我们：  
- 邮箱：motaoxx@outlook.com  
- 官网：[https://imotao.com](#)  
- GitHub：[https://github.com/motao123/friend-links-manage/](#)

---

## 结语

**友情链接管理器插件** 是管理友情链接的最佳助手。立即下载，体验高效管理的乐趣吧！

---

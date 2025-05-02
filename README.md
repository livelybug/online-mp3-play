# online-mp3-play
```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 安装 Apache 和 PHP
sudo apt install apache2 php libapache2-mod-php -y

# 验证安装是否成功
sudo systemctl status apache2  # 应显示 "active (running)"

# 创建目录（假设路径为 /var/www/html/music）
sudo mkdir -p /var/www/html/music

# 设置目录权限，确保 Apache 可读取
sudo chmod -R 755 /var/www/html/music

# 使用 SCP 上传（从本地终端执行）
scp -r /本地/MP3文件夹/*.mp3 user@服务器IP:/var/www/html/music/
```

### **配置 Apache 服务器**

确保 Apache 能正确识别 MP3 文件的 MIME 类型：

```
# 编辑 MIME 类型配置文件
sudo nano /etc/apache2/mods-enabled/mime.conf
```

找到以下行并确保取消注释（若被注释则删除行首的 `#`）：

```
AddType audio/mpeg .mp3
```

```
sudo systemctl restart apache2
```

---

# 创建 PHP 文件

sudo nano /var/www/html/index.php

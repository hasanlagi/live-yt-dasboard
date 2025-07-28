#!/bin/bash

echo "======================================="
echo "🔧 INSTALLER: Apache + PHP + FFmpeg + MySQL + DB Setup"
echo "======================================="

# Update & Upgrade
echo "📦 Updating package lists..."
sudo apt update && sudo apt upgrade -y

# Install Apache
echo "🌐 Installing Apache..."
sudo apt install -y apache2
sudo systemctl enable apache2
sudo systemctl start apache2

# Install PHP and modules
echo "🧠 Installing PHP..."
sudo apt install -y php libapache2-mod-php php-mysql
sudo systemctl restart apache2

# Install FFmpeg
echo "🎞️ Installing FFmpeg..."
sudo apt install -y ffmpeg

# Install MySQL Server
echo "🛢️ Installing MySQL Server..."
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql

# Buat Database dan Tabel
echo "🛠️ Creating database and table..."
sudo mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS live;
USE live;
CREATE TABLE IF NOT EXISTS cameras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  rtsp TEXT,
  rtmp_server TEXT,
  stream_key TEXT,
  status ENUM('ONLINE','OFFLINE') DEFAULT 'OFFLINE',
  mode ENUM('copy','transcode') DEFAULT 'copy',
  resolution VARCHAR(20) DEFAULT '1280x720'
);
EOF

# Tes PHP (optional)
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php > /dev/null

# Info
echo ""
echo "✅ INSTALLATION COMPLETE!"
echo "-------------------------"
echo "🕸 Apache Web:     http://localhost/"
echo "🧠 PHP Test Page:  http://localhost/info.php"
echo "🎞 FFmpeg Path:    $(which ffmpeg)"
echo "🛢 MySQL DB:       live"
echo "📦 Table:          cameras"

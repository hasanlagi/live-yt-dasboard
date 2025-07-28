
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

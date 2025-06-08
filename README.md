# AI 音频处理平台

基于 WebMan + ThinkPHP 构建的高性能音视频处理系统，专为 AI 驱动的音频提取、降噪、语音识别和文字转录设计。

## 功能特性

- **多格式支持**：处理音频和视频文件
- **智能队列系统**：基于 RabbitMQ 的优先级处理
- **实时进度跟踪**：基于 WebSocket 的实时更新
- **可扩展架构**：采用领域驱动设计和事件溯源
- **自动重试**：失败任务自动重试
- **RESTful API**：完整的任务管理 API

## 架构设计

系统遵循领域驱动设计（DDD）原则：

```
webman/
├── app/
│   ├── domain/          # 领域实体和值对象
│   ├── application/     # 应用服务和用例
│   ├── infrastructure/  # 外部服务集成
│   ├── controller/      # HTTP/WebSocket 控制器
│   └── process/         # 后台进程
├── config/              # 配置文件
├── database/            # 数据库迁移
└── storage/             # 文件存储
```

## 系统要求

- PHP 8.2+
- MySQL 5.7+
- Redis 6.0+
- RabbitMQ 3.8+
- Composer

## 安装步骤

1. 克隆仓库：
```bash
git clone <repository-url>
cd <project-directory>
```

2. 进入 WebMan 目录：
```bash
cd webman
```

3. 安装依赖：
```bash
composer install
```

4. 复制环境配置：
```bash
cp .env.example .env
```

5. 在 `.env` 中配置环境变量

6. 运行数据库迁移：
```bash
php database/migrate.php
```

7. 创建存储目录：
```bash
mkdir -p storage/uploads
chmod 755 storage/uploads
```

## 使用说明

### 启动服务器

```bash
php start.php start
```

守护进程模式：
```bash
php start.php start -d
```

### API 接口

#### 创建任务
```bash
POST /api/tasks
Content-Type: multipart/form-data

参数：
- process_type: audio_extract|denoise|fast_recognition|transcription|full_process
- user_id: integer
- files[]: 音频/视频文件
```

#### 获取任务详情
```bash
GET /api/tasks/{taskNumber}?user_id={userId}
```

#### 获取用户任务列表
```bash
GET /api/tasks?user_id={userId}&page={page}&page_size={pageSize}
```

#### 取消任务
```bash
POST /api/tasks/{taskNumber}/cancel
Content-Type: application/json

{
    "user_id": 1
}
```

#### 重试失败任务
```bash
POST /api/tasks/{taskNumber}/retry
Content-Type: application/json

{
    "user_id": 1
}
```

#### 获取统计信息
```bash
GET /api/tasks/statistics?user_id={userId}
```

### WebSocket 进度跟踪

连接到 WebSocket 端点：
```
ws://localhost:8787/ws
```

消息格式：
```json
// 订阅任务进度
{
    "action": "subscribe",
    "task_id": 123
}

// 取消订阅
{
    "action": "unsubscribe",
    "task_id": 123
}

// 获取当前进度
{
    "action": "get_progress",
    "task_id": 123
}
```

## 处理类型

1. **audio_extract**（优先级：8）- 从视频文件提取音频
2. **denoise**（优先级：6）- 去除音频背景噪音
3. **fast_recognition**（优先级：4）- 快速语音识别
4. **transcription**（优先级：2）- 带时间戳的完整转录
5. **full_process**（优先级：2）- 从提取到转录的完整工作流

## 队列系统

系统使用 RabbitMQ 的四个主要队列：
- `audio_extract` - 音频提取任务
- `denoise` - 降噪任务
- `fast_recognition` - 语音识别任务
- `transcription` - 文字转录任务

每个队列都有对应的死信队列用于处理失败消息。

## 后台进程

### 任务调度器
- 每 30 秒检查待处理任务
- 每 5 分钟重试失败任务
- 自动将任务分发到相应队列

## 数据库结构

### tasks
- 主要任务信息，包括状态、进度和元数据

### task_files
- 任务内的单个文件跟踪

### processing_results
- 存储处理输出和结果

### domain_events
- 事件溯源，用于审计和调试

## 开发

### 运行测试
```bash
./vendor/bin/phpunit
```

### 代码规范
```bash
./vendor/bin/php-cs-fixer fix
```

## 监控

### 健康检查
```bash
GET /api/health
```

### 日志
- 应用日志：`runtime/logs/`
- Worker 日志：查看控制台输出或守护进程日志

## 故障排除

### 常见问题

1. **数据库连接失败**
   - 检查 MySQL 是否运行
   - 验证 `.env` 中的凭据
   - 确保数据库存在

2. **RabbitMQ 连接失败**
   - 检查 RabbitMQ 是否运行
   - 验证凭据和虚拟主机
   - 确保用户有适当权限

3. **文件上传失败**
   - 检查 `storage/uploads` 目录是否存在且可写
   - 验证 PHP 配置中的文件大小限制
   - 确保允许的文件类型符合要求

## 许可证

本项目为专有软件。版权所有。

## 支持

如有问题和疑问，请联系开发团队。

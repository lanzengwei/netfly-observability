# netfly-observability

Hyperf 可观测性 Composer 包，统一采集 PHP/Swoole、HTTP、MySQL、Redis、RabbitMQ 指标与结构化日志，对接 Prometheus + Loki，并提供 docker-compose 全栈与预置 Grafana 面板。

## 功能

- Prometheus 指标：HTTP、MySQL、Redis、RabbitMQ、Swoole/PHP 运行时
- 结构化 JSON 日志，包含 `trace_id`、`project`、`service`
- 支持 `stdout` / `file`（Promtail 采集）/ `loki`（HTTP 直推）三种日志模式
- 全局与模块级开关（`OBSERVABILITY_ENABLED`、`OBSERVABILITY_HTTP` 等）
- 按 `project` 筛选指标与日志
- Grafana 预置面板，支持按 `trace_id` 查询完整请求链路日志

## 安装

```bash
composer require netfly/netfly-observability
php bin/hyperf.php vendor:publish netfly/netfly-observability
```

发布后会生成 `config/autoload/observability.php`，可通过环境变量或该文件进行配置。

---

## 配置说明

### 配置文件结构

`config/autoload/observability.php` 完整结构如下：

```php
return [
    // 全局总开关，false 时不注册中间件/监听器/进程
    'enabled' => (bool) env('OBSERVABILITY_ENABLED', true),

    // 项目与服务标识，用于指标 label 与日志字段
    'project' => env('OBSERVABILITY_PROJECT', env('APP_NAME', 'default')),
    'service' => env('OBSERVABILITY_SERVICE', env('APP_NAME', 'hyperf')),

    // 各子模块独立开关
    'modules' => [
        'http'     => (bool) env('OBSERVABILITY_HTTP', true),
        'mysql'    => (bool) env('OBSERVABILITY_MYSQL', true),
        'redis'    => (bool) env('OBSERVABILITY_REDIS', true),
        'rabbitmq' => (bool) env('OBSERVABILITY_RABBITMQ', true),
        'swoole'   => (bool) env('OBSERVABILITY_SWOOLE', true),
        'logging'  => (bool) env('OBSERVABILITY_LOGGING', true),
    ],

    'metrics' => [
        'host'      => '0.0.0.0',
        'port'      => (int) env('OBSERVABILITY_METRICS_PORT', 9502),
        'path'      => '/metrics',
        'namespace' => 'netfly',
    ],

    'logging' => [
        'driver' => env('OBSERVABILITY_LOG_DRIVER', 'stdout'), // stdout | file | loki
        'file'   => env('OBSERVABILITY_LOG_FILE', BASE_PATH . '/runtime/logs/observability.log'),
        'loki'   => [
            'endpoint'   => env('LOKI_PUSH_URL', 'http://loki:3100/loki/api/v1/push'),
            'batch_size' => 100,
            'timeout'    => 2.0,
        ],
    ],
];
```

### 环境变量一览

| 环境变量 | 对应配置项 | 说明 | 默认值 |
|---------|-----------|------|--------|
| `OBSERVABILITY_ENABLED` | `enabled` | 全局开关，`false` 时零侵入 | `true` |
| `OBSERVABILITY_PROJECT` | `project` | 项目标识，写入指标 label 与日志 | `APP_NAME` |
| `OBSERVABILITY_SERVICE` | `service` | 服务标识，写入日志 | `APP_NAME` |
| `OBSERVABILITY_HTTP` | `modules.http` | HTTP 指标与请求日志 | `true` |
| `OBSERVABILITY_MYSQL` | `modules.mysql` | MySQL 指标与查询日志 | `true` |
| `OBSERVABILITY_REDIS` | `modules.redis` | Redis 指标与命令日志 | `true` |
| `OBSERVABILITY_RABBITMQ` | `modules.rabbitmq` | RabbitMQ 指标与消息日志 | `true` |
| `OBSERVABILITY_SWOOLE` | `modules.swoole` | Swoole/PHP 运行时指标 | `true` |
| `OBSERVABILITY_LOGGING` | `modules.logging` | 结构化日志总开关 | `true` |
| `OBSERVABILITY_LOG_DRIVER` | `logging.driver` | 日志投递模式 | `stdout` |
| `OBSERVABILITY_LOG_FILE` | `logging.file` | **file 模式**下的日志文件路径 | `{BASE_PATH}/runtime/logs/observability.log` |
| `LOKI_PUSH_URL` | `logging.loki.endpoint` | **loki 模式**下的 Push API 地址 | `http://loki:3100/loki/api/v1/push` |
| `OBSERVABILITY_METRICS_PORT` | `metrics.port` | Prometheus 指标端口 | `9502` |

---

## 日志模式详解

### 三种模式对比

| 模式 | 环境变量值 | 适用场景 | 是否需要 Promtail |
|------|-----------|---------|------------------|
| `stdout` | `OBSERVABILITY_LOG_DRIVER=stdout` | 开发调试、容器 stdout 采集 | 可选（采集容器标准输出） |
| `file` | `OBSERVABILITY_LOG_DRIVER=file` | 生产环境，由 Promtail/Alloy 采集文件 | **推荐配合 Promtail** |
| `loki` | `OBSERVABILITY_LOG_DRIVER=loki` | 应用直推 Loki，无需旁路采集 | 不需要 |

---

## file 模式配置（重点）

`file` 模式将结构化 JSON 日志写入本地文件，通常配合 **Promtail** 或 **Grafana Alloy** 采集后推送到 Loki。这是生产环境最常用的方案。

### 第一步：设置环境变量

在项目 `.env` 中配置：

```dotenv
# 开启可观测性
OBSERVABILITY_ENABLED=true
OBSERVABILITY_LOGGING=true

# 使用 file 模式写本地日志文件
OBSERVABILITY_LOG_DRIVER=file

# 日志文件路径（可选，不设置则使用默认路径）
OBSERVABILITY_LOG_FILE=/opt/www/runtime/logs/observability.log

# 项目标识（用于 Loki/Grafana 按项目筛选）
OBSERVABILITY_PROJECT=order-service
OBSERVABILITY_SERVICE=api
```

### 第二步：确认配置文件

发布后的 `config/autoload/observability.php` 中 `logging` 段应如下（通常无需手动改，环境变量会自动生效）：

```php
'logging' => [
    'driver' => env('OBSERVABILITY_LOG_DRIVER', 'stdout'),
    'file'   => env('OBSERVABILITY_LOG_FILE', BASE_PATH . '/runtime/logs/observability.log'),
    'loki'   => [
        'endpoint'   => env('LOKI_PUSH_URL', 'http://loki:3100/loki/api/v1/push'),
        'batch_size' => 100,
        'timeout'    => 2.0,
    ],
],
```

> **注意：** `file` 模式下 `logging.loki` 段不会生效，可忽略。只有 `OBSERVABILITY_LOG_DRIVER=loki` 时才使用 Loki 直推。

### 第三步：确保日志目录可写

默认日志路径为 `{项目根目录}/runtime/logs/observability.log`。

包会在首次写日志时**自动创建目录**（`runtime/logs/`）。若使用自定义路径，请确保运行 Hyperf 的用户对该目录有写权限：

```bash
# 示例：手动创建并授权
mkdir -p runtime/logs
chmod 755 runtime/logs
chown www-data:www-data runtime/logs   # 按实际运行用户调整
```

### 第四步：验证日志输出

启动 Hyperf 后触发一次 HTTP 请求，检查日志文件：

```bash
tail -f runtime/logs/observability.log
```

每行一条 JSON，格式示例：

```json
{
  "timestamp": "2026-06-09T10:00:00.123Z",
  "level": "INFO",
  "message": "HTTP request completed",
  "project": "order-service",
  "service": "api",
  "trace_id": "abcdef0123456789abcdef0123456789",
  "span_id": "ae5da843c23632d0",
  "channel": "http",
  "context": {
    "method": "GET",
    "path": "/users",
    "route": "/users",
    "status": 200,
    "duration_ms": 12.345
  }
}
```

### 第五步：配置 Promtail 采集（推送到 Loki）

`file` 模式本身只负责写文件，要进入 Loki/Grafana 需要配置 Promtail。

**Promtail 配置示例**（`promtail-config.yml`）：

```yaml
clients:
  - url: http://loki:3100/loki/api/v1/push

scrape_configs:
  - job_name: hyperf-app
    static_configs:
      - targets: [localhost]
        labels:
          job: hyperf-app
          # 与 OBSERVABILITY_LOG_FILE 指向同一文件
          __path__: /var/log/hyperf/observability.log
    pipeline_stages:
      # 解析 JSON 日志行
      - json:
          expressions:
            level: level
            project: project
            service: service
            trace_id: trace_id
            channel: channel
      # 低基数字段作为 Loki label（用于筛选）
      - labels:
          project:
          service:
          level:
          channel:
      # trace_id 作为 structured metadata（避免高基数 label）
      - structured_metadata:
          trace_id:
```

**Docker Compose 挂载示例：**

```yaml
services:
  hyperf-app:
    environment:
      OBSERVABILITY_LOG_DRIVER: file
      OBSERVABILITY_LOG_FILE: /opt/www/runtime/logs/observability.log
      OBSERVABILITY_PROJECT: order-service
    volumes:
      # 将日志目录共享给 Promtail
      - hyperf-logs:/opt/www/runtime/logs

  promtail:
    image: grafana/promtail:3.2.1
    volumes:
      - ./promtail-config.yml:/etc/promtail/promtail-config.yml:ro
      - hyperf-logs:/var/log/hyperf:ro   # Promtail 读取同一份日志

volumes:
  hyperf-logs:
```

Promtail 中 `__path__` 填写容器内 Promtail 能访问到的路径，与 Hyperf 写入路径对应（通过 volume 映射到同一物理文件）。

### file 模式最小配置清单

只需以下几项即可运行：

| 配置项 | 是否必须 | 说明 |
|--------|---------|------|
| `OBSERVABILITY_ENABLED=true` | 是 | 开启包 |
| `OBSERVABILITY_LOGGING=true` | 是 | 开启日志 |
| `OBSERVABILITY_LOG_DRIVER=file` | 是 | 切换为文件模式 |
| `OBSERVABILITY_LOG_FILE` | 否 | 自定义路径，默认 `runtime/logs/observability.log` |
| `OBSERVABILITY_PROJECT` | 推荐 | 多项目环境下用于 Grafana 筛选 |
| Promtail 配置 | 推荐 | 将日志送入 Loki（file 模式不直推 Loki） |

### file 模式常见问题

**Q：设置了 `file` 但日志文件没有生成？**

1. 确认 `OBSERVABILITY_ENABLED=true` 且 `OBSERVABILITY_LOGGING=true`
2. 确认有 HTTP/DB/Redis 等事件触发（日志在事件发生时写入）
3. 检查目录写权限
4. 检查 `OBSERVABILITY_LOG_FILE` 路径是否正确

**Q：`file` 和 `loki` 能同时开启吗？**

当前版本 `OBSERVABILITY_LOG_DRIVER` 只能选一个值。若需双写，可在应用层自行扩展 Monolog Handler，或采用 `file` + Promtail 的标准采集链路。

**Q：日志文件会轮转吗？**

包内使用 Monolog `StreamHandler`，**不自带日志轮转**。生产环境建议：
- 使用 `logrotate` 管理文件大小
- 或由 Promtail 采集后由 Loki 负责存储与保留策略

---

## loki 直推模式（可选）

无需 Promtail，应用直接将日志 HTTP 推送到 Loki：

```dotenv
OBSERVABILITY_LOG_DRIVER=loki
LOKI_PUSH_URL=http://loki:3100/loki/api/v1/push
```

可在 `config/autoload/observability.php` 中调整批量大小与超时：

```php
'loki' => [
    'endpoint'   => env('LOKI_PUSH_URL', 'http://loki:3100/loki/api/v1/push'),
    'batch_size' => 100,   // 累积条数后批量推送
    'timeout'    => 2.0,   // HTTP 超时（秒）
],
```

---

## 开关控制

### 全局关闭（零侵入）

```dotenv
OBSERVABILITY_ENABLED=false
```

关闭后不会注册中间件、事件监听器和 Swoole 指标进程。

### 仅关闭某一模块

```dotenv
OBSERVABILITY_ENABLED=true
OBSERVABILITY_REDIS=false      # 不采集 Redis
OBSERVABILITY_RABBITMQ=false   # 不采集 RabbitMQ
OBSERVABILITY_LOGGING=false    # 关闭所有结构化日志
```

---

## 指标端点

默认暴露 `GET /metrics`（端口由 `OBSERVABILITY_METRICS_PORT` 控制，默认 `9502`），返回 Prometheus 文本格式。所有指标均包含 `project` label。

在 `config/autoload/server.php` 中可配置独立 metrics 端口，或通过路由注解访问 `MetricsController`。

---

## trace_id 链路查询

1. HTTP 入口读取请求头 `X-Trace-Id`，若无则自动生成 32 位 hex
2. 响应头回写 `X-Trace-Id`
3. 每条结构化日志写入 `trace_id` 字段
4. Grafana **Logs & Trace** 面板输入 `trace_id` 查询：

```logql
{project="order-service"} | trace_id="abcdef0123456789abcdef0123456789"
```

按 channel 过滤某类日志：

```logql
{project="order-service", channel="http"} | trace_id="abcdef0123456789abcdef0123456789"
{project="order-service", channel=~"mysql|redis|rabbitmq"} | trace_id="abcdef0123456789abcdef0123456789"
```

---

## Docker 全栈演示

本仓库自带完整演示栈（`file` 模式 + Promtail + Loki + Grafana）：

```bash
cd docker
docker compose up -d --build
```

| 服务 | 地址 |
|------|------|
| Example App | http://localhost:9501 |
| Metrics | http://localhost:9502/metrics |
| Prometheus | http://localhost:9090 |
| Grafana | http://localhost:3000 (admin/admin) |
| Loki | http://localhost:3100 |

触发演示数据：

```bash
curl http://localhost:9501/demo -H "X-Trace-Id: abcdef0123456789abcdef0123456789"
curl http://localhost:9502/metrics | grep netfly_
```

---

## 开发

```bash
composer install
composer test
composer analyse
composer cs-check
```

## 许可证

MIT

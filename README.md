# netfly-observability

Hyperf 可观测性 Composer 包，统一采集 PHP/Swoole、HTTP、MySQL、Redis、RabbitMQ 指标与结构化日志，对接 Prometheus + Loki，并提供 docker-compose 全栈与预置 Grafana 面板。

## 功能

- Prometheus 指标：HTTP、MySQL、Redis、RabbitMQ、Swoole/PHP 运行时
- 结构化 JSON 日志，包含 `trace_id`、`project`、`service`
- 支持 Promtail 采集与 Loki HTTP Push 双模式
- 全局与模块级开关（`OBSERVABILITY_ENABLED`、`OBSERVABILITY_HTTP` 等）
- 按 `project` 筛选指标与日志
- Grafana 预置面板，支持按 `trace_id` 查询完整请求链路日志

## 安装

```bash
composer require netfly/netfly-observability
php bin/hyperf.php vendor:publish netfly/netfly-observability
```

## 配置

发布后的配置文件位于 `config/autoload/observability.php`。

| 环境变量 | 说明 | 默认值 |
|---------|------|--------|
| `OBSERVABILITY_ENABLED` | 全局开关 | `true` |
| `OBSERVABILITY_PROJECT` | 项目标识 | `APP_NAME` |
| `OBSERVABILITY_SERVICE` | 服务标识 | `APP_NAME` |
| `OBSERVABILITY_HTTP` | HTTP 指标/日志 | `true` |
| `OBSERVABILITY_MYSQL` | MySQL 指标/日志 | `true` |
| `OBSERVABILITY_REDIS` | Redis 指标/日志 | `true` |
| `OBSERVABILITY_RABBITMQ` | RabbitMQ 指标/日志 | `true` |
| `OBSERVABILITY_SWOOLE` | Swoole 指标 | `true` |
| `OBSERVABILITY_LOGGING` | 结构化日志 | `true` |
| `OBSERVABILITY_LOG_DRIVER` | `stdout` / `file` / `loki` | `stdout` |
| `LOKI_PUSH_URL` | Loki Push API | `http://loki:3100/loki/api/v1/push` |
| `OBSERVABILITY_METRICS_PORT` | Metrics 端口 | `9502` |

## 指标端点

默认暴露 `GET /metrics`，返回 Prometheus 文本格式。所有指标均包含 `project` label。

## trace_id 链路查询

1. HTTP 请求入口读取或生成 `X-Trace-Id`
2. 响应头回写 `X-Trace-Id`
3. 结构化日志写入 `trace_id` 字段
4. Grafana Logs & Trace 面板输入 `trace_id` 查询：

```logql
{project="example-service"} | trace_id="<your-trace-id>"
```

## Docker 全栈演示

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

## 开发

```bash
composer install
composer test
composer analyse
composer cs-check
```

## 许可证

MIT

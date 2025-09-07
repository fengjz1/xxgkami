# 小小怪卡密系统 API 文档

## 概述

小小怪卡密系统提供完整的RESTful API接口，支持卡密验证、查询等功能。系统支持时间卡密和次数卡密两种类型。

## 基础信息

- **Base URL**: `http://your-domain.com/api/`
- **认证方式**: API Key
- **请求格式**: `application/x-www-form-urlencoded` 或 `application/json`
- **响应格式**: `application/json`

## 认证

所有API请求都需要在请求头中包含API密钥：

```
X-API-KEY: your_api_key_here
```

或者通过POST参数传递：

```
api_key=your_api_key_here
```

## 通用响应格式

### 成功响应
```json
{
    "code": 0,
    "message": "操作成功",
    "data": {
        // 具体数据
    }
}
```

### 错误响应
```json
{
    "code": 1,
    "message": "错误信息",
    "data": null
}
```

## 错误代码

### HTTP状态码说明

| HTTP状态码 | 说明 | 使用场景 |
|------------|------|----------|
| 200 | 成功 | 所有业务请求（成功或业务逻辑错误） |
| 401 | 未授权 | API密钥无效、缺失或已禁用 |
| 403 | 禁止访问 | API功能未启用 |
| 404 | 未找到 | 请求的API接口不存在 |
| 500 | 服务器错误 | 系统内部错误 |

### JSON错误码详细说明

| 代码 | HTTP状态码 | 错误类型 | 说明 | 常见场景 |
|------|------------|----------|------|----------|
| 0 | 200 | 成功 | 操作成功 | 卡密验证成功、查询成功 |
| 1 | 200 | 业务逻辑错误 | 业务规则验证失败 | 卡密不存在、卡密已过期、设备绑定冲突、重复验证限制等 |
| 2 | 403 | 功能未启用 | API功能被管理员禁用 | 系统设置中关闭了API功能 |
| 3 | 500 | 系统错误 | 服务器内部错误 | 数据库连接失败、系统异常等 |
| 4 | 401 | 认证失败 | API密钥相关错误 | 密钥无效、密钥已禁用、缺少密钥等 |

### 业务逻辑错误详细说明（code=1）

| 错误信息 | 说明 | 解决方案 |
|----------|------|----------|
| 卡密不存在 | 提供的卡密在系统中不存在 | 检查卡密是否正确 |
| 卡密已过期 | 时间卡密已超过有效期 | 使用未过期的卡密 |
| 此卡密使用次数已用完 | 次数卡密剩余次数为0 | 使用新的次数卡密 |
| 此卡密已被管理员禁用 | 卡密被管理员手动停用 | 联系管理员或使用其他卡密 |
| 此卡密已被其他设备使用 | 卡密已绑定到其他设备 | 使用其他卡密或联系管理员 |
| 此卡密不允许重复验证 | 卡密设置了不允许重复验证 | 使用其他卡密 |
| 请提供卡密 | 请求中缺少card_key参数 | 在请求中添加card_key参数 |
| 设备ID格式不正确 | device_id参数格式不符合要求 | 检查设备ID格式 |

**注意**: 
- 所有业务逻辑错误都返回HTTP 200状态码，通过JSON中的`code`字段区分具体的错误类型
- 只有认证、权限和系统错误才返回非200状态码
- 客户端应该根据JSON中的`code`字段进行错误处理，而不是仅依赖HTTP状态码

## API 接口

### 1. 验证卡密

验证卡密并激活（如果是新卡密）。

**接口地址**: `POST /api/verify.php`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| api_key | string | 是 | API密钥 |
| card_key | string | 是 | 卡密密钥 |
| device_id | string | 否 | 设备ID（用于设备绑定） |

**请求示例**:

```bash
curl -X POST "http://your-domain.com/api/verify.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-API-KEY: your_api_key_here" \
  -d "card_key=ABC123DEF456&device_id=device123"
```

**成功响应示例**:

时间卡密（未使用）:
```json
{
    "code": 0,
    "message": "验证成功",
    "data": {
        "card_id": 123,
        "card_key": "ABC123DEF456",
        "card_type": "time",
        "status": "valid",
        "use_time": null,
        "expire_time": "2024-12-31 23:59:59",
        "duration": 30,
        "total_count": 0,
        "remaining_count": 0,
        "device_id": "device123",
        "allow_reverify": 1
    }
}
```

次数卡密（已使用）:
```json
{
    "code": 0,
    "message": "验证成功",
    "data": {
        "card_id": 124,
        "card_key": "me0qQVmucQ2tW4ObsvVa",
        "card_type": "count",
        "status": "used",
        "use_time": "2024-01-15 10:30:00",
        "expire_time": null,
        "duration": 0,
        "total_count": 1,
        "remaining_count": 0,
        "device_id": "device123",
        "allow_reverify": 0
    }
}
```

**错误响应示例**:

卡密不存在:
```json
{
    "code": 1,
    "message": "卡密不存在",
    "data": null
}
```

卡密已过期:
```json
{
    "code": 1,
    "message": "卡密已过期",
    "data": null
}
```

设备绑定冲突:
```json
{
    "code": 1,
    "message": "此卡密已被其他设备使用",
    "data": null
}
```

API密钥无效:
```json
{
    "code": 4,
    "message": "API密钥无效或已禁用",
    "data": null
}
```

### 2. 查询卡密

查询卡密的详细信息（不激活）。

**接口地址**: `POST /api/query.php`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| api_key | string | 是 | API密钥 |
| card_key | string | 是 | 卡密密钥 |

**请求示例**:

```bash
curl -X POST "http://your-domain.com/api/query.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-API-KEY: your_api_key_here" \
  -d "card_key=ABC123DEF456"
```

**成功响应示例**:

时间卡密（已使用）:
```json
{
    "code": 0,
    "message": "查询成功",
    "data": {
        "card_id": 123,
        "card_key": "ABC123DEF456",
        "card_type": "time",
        "status": "used",
        "use_time": "2024-01-15 10:30:00",
        "expire_time": "2024-12-31 23:59:59",
        "duration": 30,
        "total_count": 0,
        "remaining_count": 0,
        "device_id": "device123",
        "allow_reverify": 1
    }
}
```

次数卡密（未使用）:
```json
{
    "code": 0,
    "message": "查询成功",
    "data": {
        "card_id": 124,
        "card_key": "me0qQVmucQ2tW4ObsvVa",
        "card_type": "count",
        "status": "valid",
        "use_time": null,
        "expire_time": null,
        "duration": 0,
        "total_count": 1,
        "remaining_count": 1,
        "device_id": null,
        "allow_reverify": 0
    }
}
```

**错误响应示例**:

卡密不存在:
```json
{
    "code": 1,
    "message": "卡密不存在",
    "data": null
}
```

缺少参数:
```json
{
    "code": 1,
    "message": "请提供卡密",
    "data": null
}
```

## 数据字段说明

### 响应数据字段

| 字段名 | 类型 | 说明 |
|--------|------|------|
| card_id | integer | 卡密在数据库中的ID |
| card_key | string | 卡密密钥 |
| card_type | string | 卡密类型：`time`(时间卡密) 或 `count`(次数卡密) |
| status | string | 卡密状态：`valid`(未使用) / `used`(已使用) / `disabled`(已停用) |
| use_time | string/null | 使用时间，格式：`YYYY-MM-DD HH:mm:ss` |
| expire_time | string/null | 过期时间，格式：`YYYY-MM-DD HH:mm:ss`（时间卡密专用） |
| duration | integer | 时长（天），时间卡密专用 |
| total_count | integer | 总次数，次数卡密专用 |
| remaining_count | integer | 剩余次数，次数卡密专用 |
| device_id | string/null | 绑定的设备ID |
| allow_reverify | integer | 是否允许重复验证：`1`(允许) / `0`(不允许) |

### 卡密类型说明

#### 时间卡密 (time)
- 基于时间限制的卡密
- 激活后从激活时间开始计算有效期
- 相关字段：`duration`（时长）、`expire_time`（过期时间）
- `total_count` 和 `remaining_count` 为 0

#### 次数卡密 (count)
- 基于使用次数的卡密
- 每次验证消耗一次使用次数
- 相关字段：`total_count`（总次数）、`remaining_count`（剩余次数）
- `duration` 为 0，`expire_time` 为 null


## 注意事项

1. **API密钥安全**: 请妥善保管API密钥，不要在客户端代码中暴露
2. **设备绑定**: 如果设置了设备绑定，同一卡密只能在一台设备上使用
3. **重复验证**: 根据 `allow_reverify` 字段判断是否允许同一设备重复验证
4. **错误处理**: 请根据返回的 `code` 字段进行相应的错误处理
5. **请求频率**: 建议控制请求频率，避免过于频繁的API调用

## 技术支持

如有问题，请联系技术支持或查看系统管理后台获取更多信息。

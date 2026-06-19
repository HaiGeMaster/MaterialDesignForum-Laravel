<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $subject }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, 'Microsoft YaHei', sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5;">
    <tr>
      <td align="center" style="padding: 24px 16px;">

        <!-- 主卡片 -->
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">

          <!-- 顶部工具栏 -->
          <tr>
            <td style="background-color: #1976D2; padding: 12px 20px 40px 20px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="32" style="vertical-align: middle;">
                    <!-- 邮箱图标，用圆形背景模拟 mdi-email-outline -->
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="width: 32px; height: 32px; background-color: rgba(255,255,255,0.15); border-radius: 50%; text-align: center; line-height: 32px;">
                          <span style="color: #ffffff; font-size: 18px;">✉</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                  <td style="vertical-align: middle; padding-left: 12px;">
                    <span style="color: #ffffff; font-size: 18px; font-weight: 500;">邮件系统</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- 浮起的内容卡片 -->
          <tr>
            <td style="padding: 0 16px;">
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: -28px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.12);">
                <!-- 邮件标题 -->
                <tr>
                  <td style="padding: 20px 20px 12px 20px;">
                    <span style="font-size: 18px; font-weight: 600; color: #212121;">{{ $subject }}</span>
                  </td>
                </tr>

                <!-- 分隔线 -->
                <tr>
                  <td style="padding: 0 20px;">
                    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 0;" />
                  </td>
                </tr>

                <!-- 邮件信息（如果有 title） -->
                @if(isset($data['title']))
                <tr>
                  <td style="padding: 12px 20px 0 20px; font-size: 13px; color: #757575;">
                    <strong style="color: #424242;">标题：</strong>{{ $data['title'] }}
                  </td>
                </tr>
                
                <!-- 分隔线 -->
                <tr>
                  <td style="padding: 0 20px;">
                    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 12px 0;" />
                  </td>
                </tr>
                @endif


                <!-- 邮件正文 -->
                <tr>
                  <td style="padding: 12px 20px 24px 20px; font-size: 15px; color: #424242; line-height: 1.8;">

                    @if(isset($data['title']))
                      <h3 style="margin: 0 0 12px 0; font-size: 16px; color: #212121;">{{ $data['title'] }}</h3>
                    @endif

                    @if(isset($data['content']))
                      <p style="margin: 0 0 12px 0;">{{ $data['content'] }}</p>
                    @endif

                    @if(isset($data['message']))
                      <p style="margin: 0 0 12px 0;">{{ $data['message'] }}</p>
                    @endif

                    @if(isset($data['body']))
                      <div style="margin: 0 0 12px 0;">{!! $data['body'] !!}</div>
                    @endif

                    @if(isset($data['items']) && is_array($data['items']))
                      <ul style="padding-left: 20px; margin: 0 0 12px 0;">
                        @foreach($data['items'] as $item)
                          <li style="margin-bottom: 4px;">{{ $item }}</li>
                        @endforeach
                      </ul>
                    @endif

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding: 20px; text-align: center;">
              <span style="font-size: 12px; color: #9e9e9e;">
                &copy; {{ date('Y') }} — <strong style="color: #757575;">{{ config('app.name', 'Material Design Forum') }}</strong>
              </span>
              @if(isset($data['footer']))
                <br />
                <span style="font-size: 12px; color: #9e9e9e;">{{ $data['footer'] }}</span>
              @endif
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>

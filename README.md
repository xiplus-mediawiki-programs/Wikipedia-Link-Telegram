# Wikipedia-Link-Telegram
Telegram中文維基百科連結Bot

## 安裝
1. 複製 ```config/config.default.php``` 至 ```config/config.php``` 並設定裡面的內容
2. 建立 ```data/``` 並給予適當的寫入權限
3. 使用 ```maintenance/database.sql``` 建立好資料庫
4. 執行 ```php maintenance/setWebhook.php```（或者你可以自行將webhook網址設定好）
5. 完成

## 機器人指令
* ```/settings``` 檢視連結回覆設定
* ```/help``` 取得指令列表
* ```/start``` 啟用所有連結回覆
* ```/stop``` 停用所有連結回覆
* ```/optin``` 啟用部分連結回覆（參數設定，使用正規表達式）
* ```/optout``` 停用部分連結回覆（參數設定，使用正規表達式）
* ```/404``` 檢測頁面存在（開啟時回應會較慢）
* ```/pagepreview``` 連結預覽（僅有一個連結時會預覽）
* ```/articlepath``` 變更文章路徑
* ```/cmdadminonly``` 調整是否只有管理員才可變更設定（此指令僅群聊有用）

請注意在群聊內使用指令必須 @ 機器人 username，例如```/settings@WikipediaLinkBot```

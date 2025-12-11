<?php 
// 处理文件上传和格式转换 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    header('Content-Type: application/json'); 
 
    // 处理文件上传 
    if (!empty($_FILES['file']['tmp_name'])) { 
        $content = file_get_contents($_FILES['file']['tmp_name']); 
        if ($content === false) { 
            echo json_encode(['error' => '无法读取上传的文件']); 
            exit; 
        } 
        echo json_encode(['uploaded' => $content]); 
        exit; 
    } 
 
    // 处理格式转换 
    $input = $_POST['input']?? ''; 
    $output = convertFormat($input); 
    echo json_encode(['output' => trim($output)]); 
    exit; 
} 
 
// 格式转换函数 
function convertFormat($input) { 
    if (strpos($input, '#EXTM3U')!== false) { 
        // M3U转TXT 
        return m3uToTxt($input); 
    } else { 
        // TXT转M3U 
        return txtToM3u($input); 
    } 
} 
 
// M3U转TXT函数 
function m3uToTxt($input) { 
    $output = ''; 
    $channel = ''; 
    $lines = explode("\n", $input); 
    foreach ($lines as $line) { 
        // 忽略含有特定字符串的行 
        if (strpos($line, '#genre#')!== false || strpos($line, '//0/0.m3u8')!== false) { 
            continue; 
        } 
        if (strpos($line, '#EXTINF') === 0) { 
            $channelParts = explode(',', $line, 2); 
            $channel = $channelParts[1]?? ''; 
            continue; 
        } 
        if (!empty(trim($line)) && $line[0]!== '#') { 
            $output.= "$channel,". trim($line). "\r\n"; 
            $channel = ''; 
        } 
    } 
    return $output; 
} 
 
// TXT转M3U函数 
function txtToM3u($input) { 
    $output = "#EXTM3U\r\n"; 
    $lines = explode("\n", $input); 
    foreach ($lines as $line) { 
        // 忽略含有特定字符串的行 
        if (strpos($line, '#genre#')!== false || strpos($line, '//0/0.m3u8')!== false) { 
            continue; 
        } 
        $parts = explode(',', trim($line), 2); 
        if (count($parts) === 2) { 
            $output.= "#EXTINF:-1,$parts[0]\r\n$parts[1]\r\n"; 
        } elseif (!empty(trim($line))) { 
            $output.= trim($line). "\r\n"; 
        } 
    } 
    return $output; 
} 
?> 
<!DOCTYPE html> 
<html lang="zh-CN"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>直播源格式转换器</title> 
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css"  rel="stylesheet"> 
    <style> 
       .container { max-width: 800px; margin-top: 2rem; } 
       .textarea-container { margin: 1rem 0; position: relative; } 
        textarea { height: 300px; resize: vertical; } 
       .button-group { gap: 0.5rem; margin-top: 1rem; display: flex; justify-content: center; } 
       .custom-file-input::-webkit-file-upload-button { visibility: hidden; } 
       .custom-file-input::before { 
            content: '选择文件'; 
            display: inline-block; 
            background: #0d6efd; 
            color: white; 
            padding: 0.375rem 0.75rem; 
            border-radius: 0.25rem; 
            cursor: pointer; 
       } 
		/* 新增标题样式 */
		.enhanced-title {
			font-family: '微软雅黑', sans-serif;
			text-shadow: 2px 2px 3px rgba(0,0,0,0.1);
			letter-spacing: 1.5px;
			background: linear-gradient(45deg, #2c3e50, #3498db);
			-webkit-background-clip: text;
			background-clip: text;
			-webkit-text-fill-color: transparent;
			padding-bottom: 0.5rem;
			border-bottom: 3px solid #3498db;
		}
    </style> 
</head> 
<body> 
    <div class="container"> 
        <h2 class="text-center mb-4 enhanced-title">📡 直播源格式转换器 | M3U↔TXT智能互转</h2> 
        <form id="converterForm" onsubmit="return false;"> 
            <div class="input-group"> 
                <input type="file" class="form-control custom-file-input" id="fileInput" accept=".m3u,.txt"> 
            </div> 
            <div class="textarea-container"> 
                <label class="form-label">输入内容：</label> 
                <textarea class="form-control" id="inputArea" placeholder="粘贴内容或上传文件...（提供M3U则转换成TXT，反之，提供TXT则转换成M3U。）"></textarea> 
            </div> 
            <div class="button-group"> 
                <button class="btn btn-primary" onclick="convert()">转换格式</button> 
                <button type="button" class="btn btn-danger" onclick="resetForm()">重置数据</button> 
            </div> 
            <div class="textarea-container"> 
                <label class="form-label">转换结果：</label> 
                <textarea class="form-control" id="outputArea" readonly></textarea> 
                <!-- 修改这里的类，将按钮水平居中 --> 
                <div class="mt-2 d-flex justify-content-center gap-2"> 
                    <button class="btn btn-success" onclick="copyResult()">复制结果</button> 
                    <button class="btn btn-info" onclick="downloadResult()">下载文件</button> 
                </div> 
            </div> 
        </form> 
    </div> 
    <script> 
        // 文件上传处理 
        document.getElementById('fileInput').addEventListener('change',  function(e) { 
            const file = e.target.files[0];  
 
            if (!file) return; 
 
            const formData = new FormData(); 
            formData.append('file',  file); 
 
            fetch('', { 
                method: 'POST', 
                body: formData 
            }) 
          .then(response => response.json())  
          .then(data => { 
                if (data.error)  { 
                    alert(data.error);  
                } else { 
                    document.getElementById('inputArea').value  = data.uploaded;  
                } 
                e.target.value  = ''; // 清空文件选择 
            }) 
          .catch(error => { 
                alert('文件上传出错：' + error.message);  
            }); 
        }); 
 
        // 格式转换函数 
        function convert() { 
            const input = document.getElementById('inputArea').value;  
 
            if (!input.trim())  return alert('请输入内容或上传文件'); 
 
            fetch('', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, 
                body: `input=${encodeURIComponent(input)}` 
            }) 
          .then(response => response.json())  
          .then(data => { 
                if (data.error)  { 
                    alert(data.error);  
                } else { 
                    document.getElementById('outputArea').value  = data.output;  
                } 
            }) 
          .catch(error => { 
                alert('格式转换出错：' + error.message);  
            }); 
        } 
 
        // 结果处理函数 
        function copyResult() { 
            const outputArea = document.getElementById('outputArea');  
 
            outputArea.select();  
            document.execCommand('copy');  
            alert('复制成功！'); 
        } 
 
        function downloadResult() { 
            const content = document.getElementById('outputArea').value;  
 
            // 设置字符编码为UTF-8，确保下载文件不会乱码 
            const blob = new Blob([content], {type: 'text/plain;charset=utf-8'}); 
            const url = URL.createObjectURL(blob);  
 
            const a = document.createElement('a');  
            a.href  = url; 
            a.download  = `live_source_${new Date().toISOString().slice(0,10)}.${content.includes('#EXTM3U')?  'm3u' : 'txt'}`; 
            a.click();  
 
            URL.revokeObjectURL(url);  
        } 
 
        // 重置表单 
        function resetForm() { 
            document.getElementById('converterForm').reset();  
            document.getElementById('inputArea').value  = ''; 
            document.getElementById('outputArea').value  = ''; 
        } 
    </script> 
</body> 
</html> 

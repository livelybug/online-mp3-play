<!DOCTYPE html>
<html>
<head>
    <title>PHP+JS MP3播放器（分页版）</title>
    <style>
        /* 新增分页相关样式 */
        .pagination { 
            margin: 20px 0;
            display: flex;
            gap: 5px;
        }
        .pagination button {
            padding: 5px 10px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            font-size: 30px;  
        }
        .pagination button.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        /* 其他样式保持原有不变 */
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 0 20px; }
        .search-box { margin-bottom: 5px; font-size: 30px;} 
        #search-input { padding: 10px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
        #file-list { list-style: none; padding: 0; }
        #file-list li { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; }
        #file-list li:hover { background: #f8f9fa; }
        #file-list li.playing { background: #e3f2fd !important; }
        #player-container { margin-top: 20px; margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px; }
        #now-playing { color: #666; margin-top: 10px;}
        audio { width: 100%; }
    </style>
</head>
<body>
    <!-- 播放器容器 -->
    <div id="player-container" style="display: none;">
    <audio id="main-player" controls></audio>
    <div id="now-playing"></div>
    </div>

    <div>
        <input class="search-box"
            type="text" 
            id="search-input" 
            placeholder="输入关键词搜索..."
            autocomplete="off"
        >
    </div>
    
    <!-- 文件列表容器 -->
    <ul id="file-list"></ul>

    <!-- 分页控件容器 -->
    <div class="pagination" id="pagination"></div>


    <script>
        // 配置常量
        const ITEMS_PER_PAGE = 20; // 每页显示数量
        let allFiles = [];        // 完整文件列表
        let filteredFiles = [];   // 过滤后的文件列表
        let currentPage = 1;      // 当前页码
        let totalPages = 1;       // 总页数
        let currentIndex = -1;    // 当前播放索引（基于完整列表）

        // 初始化：从PHP获取数据
        <?php
        $MUSIC_DIR = '/var/www/html/music';
        $files = glob($MUSIC_DIR . '/*.mp3');
        usort($files, function($a, $b) {
            return strcmp(basename($a), basename($b));
        });
        
        // 输出PHP数组到JavaScript
        echo 'allFiles = [';
        foreach ($files as $file) {
            $filename = basename($file);
            $file_url = '/music/' . rawurlencode($filename);
            echo "{ name: '" . addslashes($filename) . "', url: '$file_url' },";
        }
        echo '];';
        ?>

        // 页面加载后初始化
        window.onload = () => {
            filteredFiles = [...allFiles];
            updateDisplay();
        };

        // 更新显示（列表 + 分页）
        function updateDisplay() {
            updateFileList();
            updatePagination();
        }

        // 更新文件列表显示
        function updateFileList() {
            const list = document.getElementById('file-list');
            list.innerHTML = '';
            
            // 获取当前页数据
            const start = (currentPage - 1) * ITEMS_PER_PAGE;
            const end = start + ITEMS_PER_PAGE;
            const pageFiles = filteredFiles.slice(start, end);

            // 生成列表项
            pageFiles.forEach((file, index) => {
                const li = document.createElement('li');
                li.textContent = file.name;
                li.onclick = () => handleItemClick(file);
                if (allFiles[currentIndex]?.name === file.name) {
                    li.classList.add('playing');
                }
                list.appendChild(li);
            });
        }

        // 更新分页控件
        function updatePagination() {
            const container = document.getElementById('pagination');
            container.innerHTML = '';
            
            totalPages = Math.ceil(filteredFiles.length / ITEMS_PER_PAGE);
            
            // 生成页码按钮
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = i === currentPage ? 'active' : '';
                btn.onclick = () => gotoPage(i);
                container.appendChild(btn);
            }
        }

        // 跳转指定页码
        function gotoPage(page) {
            currentPage = page;
            updateDisplay();
        }

        // 根据所选文件跳转到对应页面
        function displayByFileIndex(currentIndex) {
            if (currentIndex < allFiles.length) {
                startPlayback(allFiles[currentIndex]);
                // 自动滚动到对应页码
                const targetPage = Math.ceil((currentIndex + 1) / ITEMS_PER_PAGE);
                if (targetPage !== currentPage) {
                    gotoPage(targetPage);
                }
            }
            updateDisplay();
        }

        // 处理文件点击
        function handleItemClick(file) {
            // 清空搜索并显示完整列表
            document.getElementById('search-input').value = '';
            filteredFiles = [...allFiles];
            currentPage = 1;
            
            // 找到点击文件在完整列表中的位置
            currentIndex = allFiles.findIndex(f => f.name === file.name);
            startPlayback(allFiles[currentIndex]);
            displayByFileIndex(currentIndex);
        }

        // 开始播放
        function startPlayback(file) {
            const player = document.getElementById('main-player');
            const nowPlaying = document.getElementById('now-playing');
            
            player.src = file.url;
            player.play();
            document.getElementById('player-container').style.display = 'block';
            nowPlaying.textContent = `正在播放：${file.name}`;
            
            player.onended = playNext;
        }

        // 自动播放下一个（基于完整列表）
        function playNext() {
            currentIndex++;
            if (currentIndex < allFiles.length) {
                startPlayback(allFiles[currentIndex]);
                // 自动滚动到对应页码
                const targetPage = Math.ceil((currentIndex + 1) / ITEMS_PER_PAGE);
                if (targetPage !== currentPage) {
                    gotoPage(targetPage);
                }
            }
            updateDisplay();
        }

        // 搜索过滤功能
        document.getElementById('search-input').addEventListener('input', function(e) {
            const keyword = e.target.value.toLowerCase().trim();
            filteredFiles = allFiles.filter(file => 
                file.name.toLowerCase().includes(keyword)
            );
            currentPage = 1; // 搜索后回到第一页
            updateDisplay();
        });
    </script>
</body>
</html>
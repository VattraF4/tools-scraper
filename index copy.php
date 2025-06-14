<?php 
require_once 'function.php';
require_once 'display.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $html = '';
    
    if (isset($_POST['input_method'])) {
        switch ($_POST['input_method']) {
            case 'url':
                if (!empty($_POST['url'])) {
                    $url = filter_var($_POST['url'], FILTER_VALIDATE_URL);
                    if ($url) {
                        $html = fetchHtml($url);
                    }
                }
                break;
                
            case 'text':
                $html = $_POST['content'] ?? '';
                break;
                
            case 'file':
                if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
                    $html = file_get_contents($_FILES['uploaded_file']['tmp_name']);
                }
                break;
        }
    }
    
    if (!empty($html)) {
        $emails = extractEmails($html);
        $phones = extractPhones($html);
        
        // Only extract images if URL was provided
        $images = [];
        if (isset($_POST['input_method']) && $_POST['input_method'] === 'url' && !empty($_POST['url'])) {
            $images = extractImages($html, $_POST['url']);
            downloadImages($images);
        }
        
        // Display results
        echo '<div id="resultsContainer">';
        displayEmails($emails);
        displayPhones($phones);
        displayImages($images);
        echo '<div class="output-block">Scraping completed in '.round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2).' seconds.</div>';
        echo '</div>';
        exit;
    } else {
        echo '<div class="alert alert-danger">No content could be extracted. Please check your input.</div>';
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Primary Meta Tags -->
    <title>Web Scraper Pro | Extract Emails, Phone Numbers & Images</title>
    <meta name="description" content="Advanced web scraping tool to extract emails, phone numbers, and images from any website. Perfect for developers, marketers, and researchers.">
    <meta name="keywords" content="web scraper, email extractor, phone number extractor, image downloader, data extraction tool">
    <meta name="author" content="Your Company Name">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/x-icon" href="assets/images/fav-image.png">
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://example.com/tools-scraper">
    <meta property="og:title" content="Web Scraper Pro | Extract Emails, Phone Numbers & Images">
    <meta property="og:description" content="Advanced web scraping tool to extract emails, phone numbers, and images from any website. Perfect for developers, marketers, and researchers.">
    <meta property="og:image" content="assets/images/scrape.jpg">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Web Scraper Pro | Extract Emails, Phone Numbers & Images">
    <meta name="twitter:description" content="Advanced web scraping tool to extract emails, phone numbers, and images from any website.">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        .tab-content { padding: 20px 0; }
        .nav-tabs { margin-bottom: 20px; }
        .download-all { margin: 20px 0; }
        .image-preview { max-height: 150px; width: auto; }
        .progress-steps .active { font-weight: bold; color: #0d6efd; }
        .progress-steps .completed { color: #198754; }
        .loader { 
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .output-block { margin-bottom: 20px; }
        .field-title { font-weight: bold; margin-bottom: 10px; }
    </style>
</head>

<body>
    <div class="container py-4">
        <h1 class="text-center mb-4">Enhanced Web Scraper</h1>

        <div class="card mb-4">
            <div class="card-body">
                <form method="post" id="scraperForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Input Method</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="input_method" id="inputUrl" value="url" checked>
                            <label class="form-check-label" for="inputUrl">Website URL</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="input_method" id="inputText" value="text">
                            <label class="form-check-label" for="inputText">Text Content</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="input_method" id="inputFile" value="file">
                            <label class="form-check-label" for="inputFile">Text File</label>
                        </div>
                    </div>

                    <div class="mb-3" id="urlInput">
                        <label for="url" class="form-label">Website URL</label>
                        <input type="url" class="form-control" name="url" placeholder="https://example.com" value="<?= isset($_POST['url']) ? htmlspecialchars($_POST['url']) : '' ?>">
                    </div>

                    <div class="mb-3 d-none" id="textInput">
                        <label for="content" class="form-label">Text Content</label>
                        <textarea class="form-control" name="content" rows="5" placeholder="Paste text content here..."><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
                    </div>

                    <div class="mb-3 d-none" id="fileInput">
                        <label for="uploaded_file" class="form-label">Text File</label>
                        <input type="file" class="form-control" name="uploaded_file" accept=".txt,.text,.html">
                    </div>

                    <button type="submit" class="btn btn-primary">Scrape Data</button>
                </form>
            </div>
        </div>

        <div class="loader-container card mb-4 d-none" id="loader">
            <div class="card-body text-center">
                <div class="loader"></div>
                <div class="progress-steps">
                    <div class="progress-step" id="step1">Fetching content...</div>
                    <div class="progress-step" id="step2">Extracting email addresses...</div>
                    <div class="progress-step" id="step3">Extracting phone numbers...</div>
                    <div class="progress-step" id="step4">Finding images...</div>
                    <div class="progress-step" id="step5">Downloading images...</div>
                    <div class="progress-step" id="step6">Compiling results...</div>
                </div>
            </div>
        </div>

        <div id="resultsContainer"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script>
        // Handle input method switching
        document.querySelectorAll('input[name="input_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('urlInput').classList.add('d-none');
                document.getElementById('textInput').classList.add('d-none');
                document.getElementById('fileInput').classList.add('d-none');
                document.getElementById(this.value + 'Input').classList.remove('d-none');
            });
        });

        document.getElementById('scraperForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const loader = document.getElementById('loader');
            const resultsContainer = document.getElementById('resultsContainer');

            // Show loader
            loader.classList.remove('d-none');
            resultsContainer.innerHTML = '';
            form.querySelector('button').disabled = true;

            // Reset progress steps
            document.querySelectorAll('.progress-step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
            document.getElementById('step1').classList.add('active');

            // Submit form via AJAX
            const formData = new FormData(form);

            // Simulate progress updates
            const steps = document.querySelectorAll('.progress-step');
            const interval = setInterval(() => {
                const currentActive = document.querySelector('.progress-step.active');
                if (currentActive) {
                    currentActive.classList.remove('active');
                    currentActive.classList.add('completed');
                    const next = currentActive.nextElementSibling;
                    if (next) {
                        next.classList.add('active');
                    }
                }
            }, 1000);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                clearInterval(interval);
                resultsContainer.innerHTML = html;
                loader.classList.add('d-none');
                form.querySelector('button').disabled = false;
            })
            .catch(error => {
                clearInterval(interval);
                console.error('Error:', error);
                loader.classList.add('d-none');
                form.querySelector('button').disabled = false;
                resultsContainer.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            });
        });

        // Download buttons would be handled after results are loaded
        document.addEventListener('click', function(e) {
            if (e.target.id === 'downloadEmails') {
                const emails = Array.from(document.querySelectorAll('#emailsResults li')).map(li => li.textContent);
                downloadAsTxt(emails, 'emails.txt');
            }
            if (e.target.id === 'downloadPhones') {
                const phones = Array.from(document.querySelectorAll('#phonesResults li')).map(li => li.textContent);
                downloadAsTxt(phones, 'phones.txt');
            }
            if (e.target.id === 'downloadImages') {
                alert('Image ZIP download would be implemented with server-side code');
            }
        });

        function downloadAsTxt(data, filename) {
            const blob = new Blob([data.join('\n')], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
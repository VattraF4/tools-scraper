<?php
require_once 'function.php';
require_once 'display.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $html = '';

    if (isset($_POST['input_method'])) {
        switch ($_POST['input_method']) {
            case 'url':
                if (!empty($_POST['url'])) {
                    $url = filter_var($_POST['url'], FILTER_VALIDATE_URL);
                    if ($url)
                        $html = fetchHtml($url);
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


        $images = [];
        $emails = [];
        $phones = [];

        if (!empty($html)) {
            $emails = extractEmails($html);
            $phones = extractPhones($html);

            // Always extract images, regardless of input method
            $baseUrl = ($_POST['input_method'] === 'url' && !empty($_POST['url']))
                ? $_POST['url']
                : 'https://example.com'; // Default base URL for relative paths
            $images = extractImages($html, $baseUrl);

            // Only download images if we have a valid base URL
            if ($_POST['input_method'] === 'url' && !empty($_POST['url'])) {
                downloadImages($images);
                file_put_contents(STORAGE_DIR . '/images/images_links.txt', implode("\n", $images));
            }
        }
        file_put_contents(STORAGE_DIR . '/emails.txt', implode("\n", $emails));
        file_put_contents(STORAGE_DIR . '/phones.txt', implode("\n", $phones));
        // file_put_contents(STORAGE_DIR . '/images/images_links.txt', implode("\n", $images));

        ob_start(); ?>
        <div id="resultsContainer">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#emailsTab">Emails
                        (<?= count($emails) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#phonesTab">Phones
                        (<?= count($phones) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#imagesTab">Images
                        (<?= count($images) ?>)</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="emailsTab">
                    <?php displayEmails($emails); ?>
                </div>
                <div class="tab-pane fade" id="phonesTab">
                    <?php displayPhones($phones); ?>
                </div>
                <div class="tab-pane fade" id="imagesTab">
                    <?php displayImages($images); ?>
                </div>
            </div>

            <div class="mt-4">
                <a href="download.php?type=emails" class="btn btn-success me-2">
                    <i class="bi bi-download"></i> Download All Emails
                </a>
                <a href="download.php?type=phones" class="btn btn-success me-2">
                    <i class="bi bi-download"></i> Download All Phones
                </a>
                <?php if (!empty($images)): ?>
                    <a href="download.php?type=images" class="btn btn-success">
                        <i class="bi bi-download"></i> Download All Images
                    </a>
                <?php endif; ?>
            </div>

            <div class="mt-3 text-muted small">
                Processed in <?= round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2) ?> seconds
            </div>
        </div>
        <?php
        echo ob_get_clean();
        exit;
    } else {
        echo '<div class="alert alert-danger">Failed to extract content. Please check your input.</div>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Primary Meta Tags -->
    <title>Web Scraper Pro | Extract Emails, Phone Numbers & Images</title>
    <meta name="description"
        content="Advanced web scraping tool to extract emails, phone numbers, and images from any website. Perfect for developers, marketers, and researchers.">
    <meta name="keywords"
        content="web scraper, email extractor, phone number extractor, image downloader, data extraction tool">
    <meta name="author" content="Your Company Name">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/x-icon" href="assets/images/fav-image.png">
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->

    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://example.com/tools-scraper">
    <meta property="og:title" content="Web Scraper Pro | Extract Emails, Phone Numbers & Images">
    <meta property="og:description"
        content="Advanced web scraping tool to extract emails, phone numbers, and images from any website. Perfect for developers, marketers, and researchers.">
    <meta property="og:image" content="assets/images/scrape.jpg">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Web Scraper Pro | Extract Emails, Phone Numbers & Images">
    <meta name="twitter:description"
        content="Advanced web scraping tool to extract emails, phone numbers, and images from any website.">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">

    <style>
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .image-card {
            height: 100%;
        }

        .image-card img {
            object-fit: contain;
            height: 200px;
            width: 100%;
        }

        .progress-step {
            transition: all 0.3s;
        }

        .progress-step.active {
            font-weight: bold;
            color: #0d6efd;
        }

        .progress-step.completed {
            color: #198754;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-body">
                <h1 class="text-center mb-4">Web Scraper Pro</h1>

                <form id="scraperForm" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Input Source:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="input_method" id="inputUrl" value="url"
                                checked>
                            <label class="form-check-label" for="inputUrl">Website URL</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="input_method" id="inputText"
                                value="text">
                            <label class="form-check-label" for="inputText">Text Content</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="input_method" id="inputFile"
                                value="file">
                            <label class="form-check-label" for="inputFile">HTML/TXT File</label>
                        </div>
                    </div>

                    <div id="urlInput">
                        <div class="mb-3">
                            <label for="url" class="form-label">Website URL</label>
                            <input type="url" class="form-control" name="url" placeholder="https://example.com"
                                required>
                        </div>
                    </div>

                    <div id="textInput" class="d-none">
                        <div class="mb-3">
                            <label for="content" class="form-label">Text Content</label>
                            <textarea class="form-control" name="content" rows="5"
                                placeholder="Paste your content here..."></textarea>
                        </div>
                    </div>

                    <div id="fileInput" class="d-none">
                        <div class="mb-3">
                            <label for="uploaded_file" class="form-label">Select File</label>
                            <input type="file" class="form-control" name="uploaded_file" accept=".txt,.html,.htm">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-search"></i> Scrape Data
                    </button>
                </form>
            </div>
        </div>

        <div id="loader" class="card mt-4 d-none">
            <div class="card-body text-center">
                <div class="loader"></div>
                <div class="progress-steps mt-3">
                    <div class="progress-step active" id="step1">Fetching Content</div>
                    <div class="progress-step" id="step2">Extracting Emails</div>
                    <div class="progress-step" id="step3">Extracting Phones</div>
                    <div class="progress-step" id="step4">Finding Images</div>
                    <div class="progress-step" id="step5">Processing Results</div>
                </div>
            </div>
        </div>

        <div id="resultsContainer" class="mt-4"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle input fields
        document.querySelectorAll('input[name="input_method"]').forEach(radio => {
            radio.addEventListener('change', function () {
                document.querySelectorAll('#urlInput, #textInput, #fileInput').forEach(el => {
                    el.classList.add('d-none');
                });
                document.getElementById(this.value + 'Input').classList.remove('d-none');
            });
        });

        // Form submission
        document.getElementById('scraperForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const form = this;
            const loader = document.getElementById('loader');
            const resultsContainer = document.getElementById('resultsContainer');

            loader.classList.remove('d-none');
            resultsContainer.innerHTML = '';
            form.querySelector('button').disabled = true;

            // Reset progress steps
            document.querySelectorAll('.progress-step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
            document.getElementById('step1').classList.add('active');

            // Animate progress
            const steps = document.querySelectorAll('.progress-step');
            const interval = setInterval(() => {
                const active = document.querySelector('.progress-step.active');
                if (active) {
                    active.classList.remove('active');
                    active.classList.add('completed');
                    const next = active.nextElementSibling;
                    if (next) next.classList.add('active');
                }
            }, 800);

            // Submit form
            fetch('', {
                method: 'POST',
                body: new FormData(form)
            })
                .then(response => response.text())
                .then(html => {
                    clearInterval(interval);
                    resultsContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error(error);
                    resultsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        Error: ${error.message || 'Request failed'}
                    </div>
                `;
                })
                .finally(() => {
                    loader.classList.add('d-none');
                    form.querySelector('button').disabled = false;
                });
        });
    </script>
</body>

</html>
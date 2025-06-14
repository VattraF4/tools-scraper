<?php
// require "function.php" ;
$itemsPerPage = 30;

function paginate($items, $pageParam) {
    $page = isset($_GET[$pageParam]) ? max(1, (int)$_GET[$pageParam]) : 1;
    $total = count($items);
    $pages = ceil($total / $GLOBALS['itemsPerPage']);
    $offset = ($page - 1) * $GLOBALS['itemsPerPage'];
    
    return [
        'items' => array_slice($items, $offset, $GLOBALS['itemsPerPage']),
        'page' => $page,
        'pages' => $pages,
        'total' => $total
    ];
}

function paginationLinks($pageParam, $data, $target) {
    if ($data['pages'] <= 1) return '';
    
    $links = [];
    $range = 2;
    $start = max(1, $data['page'] - $range);
    $end = min($data['pages'], $data['page'] + $range);
    
    if ($data['page'] > 1) {
        $links[] = sprintf(
            '<li class="page-item"><a class="page-link" href="?%s=%d&tab=%s">Previous</a></li>',
            $pageParam,
            $data['page'] - 1,
            $target
        );
    }
    
    if ($start > 1) {
        $links[] = '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $data['page'] ? ' active' : '';
        $links[] = sprintf(
            '<li class="page-item%s"><a class="page-link" href="?%s=%d&tab=%s">%d</a></li>',
            $active,
            $pageParam,
            $i,
            $target,
            $i
        );
    }
    
    if ($end < $data['pages']) {
        $links[] = '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    
    if ($data['page'] < $data['pages']) {
        $links[] = sprintf(
            '<li class="page-item"><a class="page-link" href="?%s=%d&tab=%s">Next</a></li>',
            $pageParam,
            $data['page'] + 1,
            $target
        );
    }
    
    return '<ul class="pagination">'.implode('', $links).'</ul>';
}

function displayEmails($emails) {
    $data = paginate($emails, 'email_page');
    
    echo '<div class="card mb-4">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h5 class="mb-0">Email Addresses</h5>';
    echo '<a href="download.php?type=emails" class="btn btn-sm btn-success">Download All</a>';
    echo '</div>';
    
    if (!empty($data['items'])) {
        echo '<ul class="list-group list-group-flush">';
        foreach ($data['items'] as $email) {
            echo '<li class="list-group-item">'.htmlspecialchars($email).'</li>';
        }
        echo '</ul>';
        echo '<div class="card-footer">'.paginationLinks('email_page', $data, 'emails').'</div>';
    } else {
        echo '<div class="card-body text-muted">No email addresses found</div>';
    }
    echo '</div>';
}

function displayPhones($phones) {
    $data = paginate($phones, 'phone_page');
    
    echo '<div class="card mb-4">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h5 class="mb-0">Phone Numbers For Testing (Khmer Only)</h5>';
    echo '<a href="download.php?type=phones" class="btn btn-sm btn-success">Download All</a>';
    echo '</div>';
    
    if (!empty($data['items'])) {
        echo '<ul class="list-group list-group-flush">';
        foreach ($data['items'] as $phone) {
            echo '<li class="list-group-item">'.htmlspecialchars($phone).'</li>';
        }
        echo '</ul>';
        echo '<div class="card-footer">'.paginationLinks('phone_page', $data, 'phones').'</div>';
    } else {
        echo '<div class="card-body text-muted">No phone numbers found</div>';
    }
    echo '</div>';
}

function displayImages($images) {
    $data = paginate($images, 'image_page');
    
    echo '<div class="card mb-4">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h5 class="mb-0">Images</h5>';
    if (!empty($images)) {
        echo '<a href="download.php?type=images" class="btn btn-sm btn-success">Download All</a>';
    }
    echo '</div>';
    
    if (!empty($data['items'])) {
        echo '<div class="card-body">';
        echo '<div class="row">';
        foreach ($data['items'] as $img) {
            $name = basename(parse_url($img, PHP_URL_PATH));
            $path = 'storage/images/'.$name;
            
            if (file_exists(IMAGES_DIR.'/'.$name)) {
                echo '<div class="col-md-2 mb-3">';
                echo '<div class="card image-card">';
                echo '<img src="'.$path.'" class="card-img-top" alt="'.htmlspecialchars($name).'">';
                echo '<div class="card-body">';
                echo '<p class="card-text text-truncate small">'.htmlspecialchars($name).'</p>';
                echo '<a href="'.$path.'" download="'.$name.'" class="btn btn-sm btn-primary w-100">Download</a>';
                echo '</div></div></div>';
            }
        }
        echo '</div>';
        echo '<div class="mt-3">'.paginationLinks('image_page', $data, 'images').'</div>';
        echo '</div>';
    } else {
        echo '<div class="card-body text-muted">No images found</div>';
    }
    echo '</div>';
}


?>
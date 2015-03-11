<?php include_once('conf.php'); ?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Webnalist Merchant Demo Store</title>
</head>
<body>

<h1>Webnalist Merchant Demo Store</h1>
<ul>
    <li class="wn-item"
        data-wn-url="http://demo.webnalist.com/article.php?id=1<?php if (SANDBOX_MODE): ?>#purchased<?php endif; ?>">
        <a href="#">
            Purchased Article #1 Title <em>Price: <span class="wn-price">...</span> zł</em>
        </a>
    </li>
    <li class="wn-item" data-wn-url="http://demo.webnalist.com/article.php?id=2">
        <a href="#">
            New Article #2 Title <em>Price: <span class="wn-price">...</span> zł</em>
        </a>
    </li>
    <li class="wn-item" data-wn-url="http://demo.webnalist.com/article.php?id=3">
        <a href="#">
            New Article #3 Title <em>Price: <span class="wn-price">...</span> zł</em>
        </a>
    </li>
</ul>

<a href="#" class="wn-item" data-wn-url="http://demo.webnalist.com/article.php?id=1">Simple link to Article
    #1 &raquo;</a>

<script>
    var WN = WN || {};
    WN.options = {
        loadPrices: true,
        <?php if (SANDBOX_MODE) : ?>
        sandbox: {
            url: '<?php echo PAGE_URL ?>'
        }
        <?php endif; ?>
    }
</script>
<script src="js/webnalist.min.js"></script>

</body>
</html>
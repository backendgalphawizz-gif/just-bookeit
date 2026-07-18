<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>

    <style>
        #loader{
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background:#fff;
            display:flex;
            justify-content:center;
            align-items:center;
            z-index:9999;
        }

        #content{
            display:none;
        }

        img{
            max-width:300px;
        }
    </style>
</head>
<body>

<div id="loader">
    <img src="{{ $image }}">
</div>

<div id="content">
    Your website content here...
</div>

<script>
setTimeout(function(){
    document.getElementById('loader').style.display='none';
    document.getElementById('content').style.display='block';
},3000);
</script>

</body>
</html>
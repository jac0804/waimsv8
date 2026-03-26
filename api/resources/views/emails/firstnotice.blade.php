<!DOCTYPE html>
<html>

<head>
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            color: #B0BEC5;
            display: table;
            font-weight: 100;
            font-family: 'Lato', sans-serif;
        }

        .container {
            text-align: left;
            display: table-cell;
            vertical-align: middle;
        }

        .content {
            text-align: left;
            display: inline-block;
        }

        .title {
            font-size: 72px;
            margin-bottom: 40px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="content">
            <div>{!! $data['msg'] !!}</div>
        </div>
    </div>
</body>

</html>
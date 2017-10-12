<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }



            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: .1rem;
            }

            .m-b-md {
                margin-bottom: 30px;
            }

            p{
                color: navy
            }
            .pl-10px{
                padding-left: 10px;
            }
            .pl-50px{
                padding-left: 50px;
            }
        </style>
    </head>
    <body>
        <div class="full-height">

            <div class="row pl-10px">
                <h3>
                    CsvImport
                </h3>

                <div class="pl-50px">
                    <div class="col-md-10">
                        <h5>
                        <p>
                            This link shows up a single page app in AngularJs demonstrating the basic<br>
                            features of CsvImporter class developed by me to generalise importing data<br>
                            into arbitrary models with capability of preprocessing it through pipes and<br>
                            also defining a master detail relashionship on the record level.
                        </p>
                        </h5>
                        <a href="./imports/csv/show_importer"><b>CsvImporter sample angularJs spa application</b></a>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

<html>
<head>

    <link href="https://cdn.jsdelivr.net/npm/vuetify@3.0.1/dist/vuetify.min.css" rel="stylesheet">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vuetify@3.0.1/dist/vuetify.min.js"></script>
</head>

<body>
<div id="app">
    <v-app>
        <v-container class="center h-100">
            <div class="d-flex align-center h-100 flex-column justify-center h-100">
                <template v-if="showSelector">
                    <v-card
                            v-for="item in items"
                            width="400"
                            :text="item.name"
                            class="mb-4"
                            @click="install(item.link)"
                    ></v-card>
                </template>
                <template v-else>
                    <h3>{{message}}</h3>
                    <div v-if="state == 'load_archive'" class="w-50" style="margin-top: 20px">
                        <v-progress-linear
                                v-model="progress"
                                height="25"
                        >
                            <strong>{{ Math.ceil(progress) }}%</strong>
                        </v-progress-linear>
                    </div>
                </template>
            </div>
        </v-container>
    </v-app>
</div>

<?php
function logProgress($data)
{
    file_put_contents('progress.json', json_encode($data));
}

function parseHeaders($stream){
    $metadata = stream_get_meta_data($stream);
    $headers = [];
    foreach ($metadata['wrapper_data'] as $datum){
        [$header, $value] = explode(':', $datum, 2);
        $headers[$header] = trim($value);
    }

    return $headers;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    ignore_user_abort(true);
    set_time_limit(0);
    $data = json_decode(file_get_contents('php://input'), true);
    $link = $data['link'];

    logProgress([
        'message' => 'Начало загрузки',
        'state' => 'load_start'
    ]);

    $remote = fopen($link, 'r');
    $local = fopen('bitrix.tar.gz', 'w');

    $size = intval(parseHeaders($remote)['Content-Length'])/8;

    $loaded = 0;
    $lastPercent = 0;
    while ($data = fread($remote, 10000)) {
        $loaded += 1024;
        fwrite($local, $data);
        $time = time();
        $percent = round(($loaded/$size)*100);
        if($percent > $lastPercent){
            logProgress([
                'message' => 'Загрузка',
                'state' => 'load_archive',
                'progress' => $percent
            ]);
        }

        $lastPercent = $percent;
    }

    logProgress([
        'message' => 'Распаковка...',
        'state' => 'unpack',
    ]);

    unlink('index.php');
    exec('tar -C . -xzvf bitrix.tar.gz');
    unlink('bitrix.tar.gz');

    logProgress([
        'message' => 'Распаковка завершена',
        'state' => 'success',
    ]);
    sleep(5);
    unlink('progress.json');
    return;
}
?>


<script>
    addEventListener('DOMContentLoaded', e => {
        Vue.createApp({
            data: () => ({
                showSelector: true,
                items: [
                    {
                        name: 'Start',
                        link: 'https://www.1c-bitrix.ru/download/start_encode.tar.gz'
                    },
                    {
                        name: 'Standart',
                        link: 'https://www.1c-bitrix.ru/download/standard_encode.tar.gz'
                    },
                    {
                        name: 'Small Business',
                        link: 'https://www.1c-bitrix.ru/download/small_business_encode.tar.gz'
                    },
                    {
                        name: 'Business',
                        link: 'https://www.1c-bitrix.ru/download/business_encode.tar.gz'
                    },
                    {
                        name: 'Bitrix24',
                        link: 'https://www.1c-bitrix.ru/download/portal/bitrix24_shop_encode.tar.gz'
                    }
                ],
                message: '',
                progress: 0,
                state: ''
            }),
            methods: {
                install(link) {
                    fetch(`/`, {
                        method: 'post',
                        body: JSON.stringify({
                            link
                        })
                    })
                        .then(r => location.reload())
                },
                loadProgress() {
                    fetch('/progress.json', {
                        cache: 'no-store'
                    })
                        .then(r => {
                            if (r.ok)
                                return r.json();
                        })
                        .then(r => {
                            if (r) {
                                this.state = r.state;
                                this.progress = r.progress;
                                this.message = r.message;

                                this.showSelector = false;

                                if (r.state === 'success') {
                                    location.reload();
                                }
                            }
                        });
                }
            },
            created() {
                setInterval(() => {
                    this.loadProgress();
                }, 1000);
            }
        }).use(Vuetify.createVuetify()).mount('#app');
    });
</script>
</body>
</html>
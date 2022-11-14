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
        <div v-if="showSelector" class="d-flex align-center h-100 flex-column justify-center h-100">
            <v-card
                v-for="item in items"
                width="400"
                :text="item.name"
                class="mb-4"
                @click="install(item.link)"
            ></v-card>
        </div>
        <div v-else>
            {{progressMessage}}
        </div>
    </v-container>
</v-app>
</div>

<?php
    function logProgress($data){
        file_put_contents('progress-log.txt', $data);
    }


    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        ignore_user_abort(true);
        set_time_limit(0);
        $data = json_decode(file_get_contents('php://input') , true);
        $link = $data['link'];

        logProgress('Загрузка архива');

        $remote = fopen($link, 'r');
        $local = fopen('bitrix.tar.gz', 'w');

        $loaded = 0;

        while($data = fread($remote, 10000)){
            $loaded += 1024;
            fwrite($local, $data);
            $time = time();
            logProgress("Загружено {$loaded} байт");
        }

        logProgress('Распаковка...');

        unlink('index.php');
        exec('tar -C . -xzvf bitrix.tar.gz');
        unlink('bitrix.tar.gz');

        logProgress('success');
        return;
    }
?>


<script>
addEventListener('DOMContentLoaded', e => {
    Vue.createApp({
        data: ()=>({
            showSelector: true,
            items:[
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
            progressMessage: ''
        }),
        methods:{
            install(link){
                fetch(`/`, {
                    method: 'post',
                    body: JSON.stringify({
                        link
                    })
                }).then(r => location.reload())
            },
            loadProgress(){
                fetch('/progress-log.txt')
                .then(r => {
                    if(r.ok)
                        return r.text();
                })
                .then(r => {
                    if(r){
                        this.showSelector = false;
                        this.progressMessage = r;
                    }

                    if(r == 'success'){
                        location.reload();
                    }
                });
            }
        },
        created(){
            setInterval(() => {
                this.loadProgress();
            }, 1000);
        }
    }).use(Vuetify.createVuetify()).mount('#app');
});
</script>
</body>
</html>
FROM gridorius/bitrix-env:latest

ARG BITRIX_VERSION=business_encode

USER www-data

RUN curl -sL "https://www.1c-bitrix.ru/download/${BITRIX_VERSION}.tar.gz" --output 'bitrix.tar.gz'

RUN tar -C . -xzvf bitrix.tar.gz

RUN rm -rf bitrix.tar.gz
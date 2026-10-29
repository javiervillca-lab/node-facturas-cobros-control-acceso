FROM nginx:alpine as base

RUN echo 'config nginx';

FROM base as dev

COPY docker/nginx/certs/docker.crt /etc/nginx/
COPY docker/nginx/certs/docker.key /etc/nginx/
COPY docker/nginx/default.conf /etc/nginx/conf.d/
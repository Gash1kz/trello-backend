FROM nginx:stable-alpine

RUN apk add --no-cache bash

COPY docker/nginx/conf.d /etc/nginx/conf.d

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]

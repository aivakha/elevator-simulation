FROM node:22-alpine AS build

WORKDIR /app/frontend

COPY frontend/package*.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build

FROM nginx:1.27-alpine

RUN apk add --no-cache gettext

COPY --from=build /app/frontend/dist /usr/share/nginx/html
COPY docker/railway/frontend/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/railway/frontend/start-frontend.sh /start-frontend.sh

RUN chmod +x /start-frontend.sh

EXPOSE 8080

CMD ["/start-frontend.sh"]

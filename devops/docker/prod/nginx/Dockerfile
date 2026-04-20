FROM node:lts-alpine AS frontend-builder
WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ .
RUN npm run build

FROM nginx:1.25-alpine
COPY devops/docker/prod/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY backend/public /var/www/html/public
COPY --from=frontend-builder /app/dist /var/www/html/public/app

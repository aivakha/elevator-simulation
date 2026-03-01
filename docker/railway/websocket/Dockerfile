FROM node:22-alpine

WORKDIR /app/websocket

COPY websocket/package*.json ./
RUN npm ci --omit=dev

COPY websocket/ ./

EXPOSE 8080

CMD ["npm", "run", "start"]

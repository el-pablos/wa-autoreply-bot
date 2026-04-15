FROM node:20-alpine

WORKDIR /app

# Install dependencies produksi
COPY package.json ./
RUN npm install --omit=dev

# Copy source
COPY src/ ./src/

# Direktori auth_info (persistent via volume)
RUN mkdir -p ./auth_info

EXPOSE 3001

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
  CMD wget -qO- http://localhost:3001/health || exit 1

CMD ["node", "src/index.js"]

FROM node:20-alpine

WORKDIR /app

# Install dependencies first (for better caching)
COPY package.json package-lock.json* ./
RUN npm install

# Install curl for healthcheck
RUN apk add --no-cache curl

# Copy source code
COPY . .

# Expose Next.js port
EXPOSE 3000

# Set environment
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

# Build Arguments
ARG MOODLE_INTERNAL_URL
ARG MOODLE_PUBLIC_URL
ARG NEXT_PUBLIC_MOODLE_PUBLIC_URL
ARG MOODLE_SERVICE_NAME
ARG MOODLE_ADMIN_TOKEN
ARG MOODLE_WS_TOKEN

# Set ENVs for build time
ENV MOODLE_INTERNAL_URL=$MOODLE_INTERNAL_URL
ENV MOODLE_PUBLIC_URL=$MOODLE_PUBLIC_URL
ENV NEXT_PUBLIC_MOODLE_PUBLIC_URL=$NEXT_PUBLIC_MOODLE_PUBLIC_URL
ENV MOODLE_SERVICE_NAME=$MOODLE_SERVICE_NAME
ENV MOODLE_ADMIN_TOKEN=$MOODLE_ADMIN_TOKEN
ENV MOODLE_WS_TOKEN=$MOODLE_WS_TOKEN

# Build the application
RUN npm run build

# Start the application
CMD ["npm", "start"]

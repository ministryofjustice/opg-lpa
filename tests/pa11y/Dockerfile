# A minimal Docker image with Node and Puppeteer

FROM node:lts-alpine

RUN apk add --no-cache \
      chromium \
      nss \
      freetype \
      freetype-dev \
      harfbuzz \
      ca-certificates \
      ttf-freefont \
      nodejs \
      yarn

# Tell Puppeteer to skip installing Chrome. We'll be using the installed package.
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD true 
# Tell puppeteer where to look for chromium and install here.
ENV CHROMIUM_PATH /usr/bin/chromium-browser

# Puppeteer v1.19.0 works with Chromium 77.
RUN yarn add puppeteer@1.19.0

WORKDIR /src/app

RUN npm install
RUN npm install -g pa11y
RUN ls
RUN which pa11y

#CMD [ "node", "-r", "esm", "src/server.js"]

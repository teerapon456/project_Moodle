#!/bin/bash
mkdir -p docker/nginx/certs
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/certs/selfsigned.key \
  -out docker/nginx/certs/selfsigned.crt \
  -subj "/C=TH/ST=Bangkok/L=Bangkok/O=MyHR/OU=IT/CN=localhost"

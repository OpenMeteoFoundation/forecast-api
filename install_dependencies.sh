#!/bin/bash

sudo apt-get install libnetcdf-dev

cd dataserver
rm -rf threads wqueue tcpsockets
git clone https://github.com/vichargrave/threads.git
git clone https://github.com/vichargrave/wqueue.git
git clone https://github.com/vichargrave/tcpsockets.git
#!/bin/bash

sudo cp sysctl.conf /etc/sysctl.d/30-forecast-api-shm.conf
sudo sysctl -p
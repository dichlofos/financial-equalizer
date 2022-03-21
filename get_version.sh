#!/usr/bin/env bash

. deploy-tools/installer/installer.sh

( cd . && echo "financial-equalizer:$(xcms_get_version)" )
( cd site/xengine && echo "xengine:$(xcms_get_version)" )
( cd deploy-tools && echo "deploy-tools:$(xcms_get_version)" )

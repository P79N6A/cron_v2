# coding:utf-8
from __future__ import absolute_import
from celery import Celery
from lcs_task.db import getDbWPool,getDbRPool,getRedisWPool,getRedisRPool
from lcs_task.db.mysql import Mysql

app = Celery("service")
app.config_from_object("lcs_task.celery_config")

"""
mysql redis 连接池
"""
db_w_pool = getDbWPool()
db_r_pool = getDbRPool()
redis_w_pool = getRedisWPool()
redis_r_pool = getRedisRPool()

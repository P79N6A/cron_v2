# coding:utf-8
"""
mysql、redis连接池方法
"""
from __future__ import absolute_import

import redis
import MySQLdb
from MySQLdb.cursors import DictCursor
from DBUtils.PooledDB import PooledDB
from lcs_task.config import *

def getDbWPool():
    pool = PooledDB(creator=MySQLdb, mincached=1 , maxcached=20 ,host=DBHOST_W, port=DBPORT_W , user=DBUSER_W , passwd=DBPWD_W,db=DBNAME_W,use_unicode=False,charset=DBCHAR_W,cursorclass=DictCursor)
    return pool

def getDbRPool():
    pool = PooledDB(creator=MySQLdb, mincached=1 , maxcached=20 ,
                    host=DBHOST_R, port=DBPORT_R , user=DBUSER_R , passwd=DBPWD_R ,
                    db=DBNAME_R,use_unicode=False,charset=DBCHAR_R,cursorclass=DictCursor)
    return pool

def getRedisWPool():
    pool = redis.ConnectionPool(host=REDIS_HOST_W,port=REDIS_PROT_W)
    return pool

def getRedisRPool():
    pool = redis.ConnectionPool(host=REDIS_HOST_R,port=REDIS_PROT_R)
    return pool
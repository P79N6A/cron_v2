#*coding=utf-8
from __future__ import absolute_import
from lcs_task import app
import os
import sys
sys.path.append(os.path.abspath('%s/..' % sys.path[0]))

@app.task
def plan_test():
    return 'plan'


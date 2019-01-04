from __future__ import absolute_import
from celery.schedules import crontab

BROKER_URL = "redis://10.55.30.35:6379"

CELERY_TIMEZONE = "Asia/Shanghai"
CELERY_IMPORTS = (
    "lcs_task.plan_task",
    "lcs_task.view_task",
    "lcs_task.order_task",
    "lcs_task.circle_task",    
)

CELERY_ROUTES={
    "lcs_task.plan_task.plan_test":{"queue":"celery_plan"},
    "lcs_task.circle_task.sendToCircle":{"queue":"celery_circle"}      
}

CELERYBEAT_SCHEDULE = {
    "plan_test_1_min": {
        "task": "lcs_task.plan_task.plan_test",        
        "schedule": crontab(minute="*/1"),
        "args": ()
    },
    "view_test_1_min": {
        "task": "lcs_task.view_task.view_test",        
        "schedule": crontab(minute="*/1"),
        "args": ()
    },
   "close_expire_order_1_min": {
        "task": "lcs_task.order_task.closeExpireOrder",        
        "schedule": crontab(minute="*/1"),
        "args": ()
    },
}

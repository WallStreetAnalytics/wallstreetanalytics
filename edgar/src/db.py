import
from typing import NoReturn

from mongoengine import connect


def initialize() -> NoReturn:
    """init a connection to mongodb
    """
    connect(
        os.getenv("MONGODB_DATABASE", ""),
        host=os.getenv("MONGODB_HOST", ""),
        port=os.getenv("MONGODB_PORT", 27017),
        username=os.getenv("MONGODB_USER", ""),
        password=os.getenv("MONGODB_PASS", ""),
    )

from asyncio import get_event_loop, as_completed
import itertools
from typing import List, Iterable

from aiohttp import ClientSession, ClientTimeout

from src.const import BASE_SEC_URL


def chunks(iterable: Iterable, size: int) -> Iterable:
    it = iter(iterable)
    item = list(itertools.islice(it, size))
    while item:
        yield item
        item = list(itertools.islice(it, size))


class AsyncDownloader:
    def __init__(self, urls: List[str]):
        self.urls = urls
        self.loop = get_event_loop()

    def __iter__(self):
        download_futures = [self.download(url) for url in self.urls]
        for download_future in as_completed(download_futures):
            yield self.loop.run_until_complete(download_future)

    async def download(self, url: str):
        async with ClientSession(timeout=ClientTimeout(total=600)) as session:
            async with session.get(f"{BASE_SEC_URL}{url}") as response:
                if response.status == 200:
                    content = await response.read()
                    return url, content
                return None, None

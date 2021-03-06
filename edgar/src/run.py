# main
from typing import Generic, NoReturn

import edgar
import pandas as pd

from src.utils import AsyncDownloader, chunks


class EdgarHandler:
    def __init__(
        self, download_folder: str = "./downloads", year: int = 2021
    ) -> NoReturn:
        self.download_folder = download_folder
        self.year = year

    def extract_10_q(self) -> Generic:
        edgar.download_index(
            self.download_folder, self.year, skip_all_present_except_last=False
        )

        # TODO: probabaly want to join a master list or something
        df = pd.read_csv(
            f"{self.download_folder}/2021-QTR1.tsv", sep="|", header=None, usecols=[2, 4], names=["type", "url"]
        )

        return df[df["type"] == "10-Q"]

    def process(self):
        data_frame = self.extract_10_q()

        for batch in chunks(data_frame["url"], 60):
            for url, text in AsyncDownloader(batch):
                if url and text:
                    # do some rad parsing here
                    print(url)


if __name__ == "__main__":
    EdgarHandler().process()

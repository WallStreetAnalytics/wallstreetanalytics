import requests
import json
import pandas as pd
import numpy as np
pd.set_option('display.max_columns', 100)
pd.set_option('display.max_rows', 500)
pd.set_option('colheader_justify', 'center')
import warnings
import datetime
from datetime import datetime
from datetime import timedelta
import time
warnings.filterwarnings("ignore")





def get_daily_update(url,frames,verify=False):
	response = requests.get(url, verify=verify)
	data = str(response.text)
	df = pd.DataFrame([x.split(',') for x in data.split('\n')])
	new_header = df.iloc[0] #grab the first row for the header
	df = df[1:] #take the data less the header row
	df.columns = new_header #set the header row as the df header
	df = df.loc[:, df.columns.notnull()]
	df.columns = ['date', 'fund', 'company', 'ticker', 'cusip', 'shares', 'market_value', 'weight']
	df.drop(columns=['cusip'], inplace=True)
	df['company'] = df['company'].apply(lambda x: str(x).replace(' "',''))
	df['company'] = df['company'].apply(lambda x: str(x).replace('"',''))
	df['ticker'] = df['ticker'].apply(lambda x: str(x).replace(' "',''))
	df['ticker'] = df['ticker'].apply(lambda x: str(x).replace('"',''))
	df.replace(r'^\s*$', np.nan, regex=True, inplace=True)
	df['count'] = df.count(axis=1, level=None, numeric_only=False)
	blank_list = list(df.index[df['count']==0])
	first_blank = blank_list[0]-1
	df.drop(columns=['count'], inplace=True)
	df = df.iloc[0:int(first_blank)].copy()
	df['date']= pd.to_datetime(df['date'])
	df['shares'] = pd.to_numeric(df['shares'],errors='coerce')
	# df['market_value'] = pd.to_numeric(df['market_value'],errors='coerce')
	df['weight'] = pd.to_numeric(df['weight'],errors='coerce')
	# df['id'] = df['fund'] + df['ticker']
	# df['price'] = df['market_value'] / df['shares']
	# now = datetime.now()
	# date_time = now.strftime("%m_%d_%Y_%H%M%S")
#     print(date_time)
	# df['last_update_time'] = datetime.now()
	frames.append(df)

# url_list = []
# url_list.append('https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_AUTONOMOUS_TECHNOLOGY_&_ROBOTICS_ETF_ARKQ_HOLDINGS.csv')
# url_list.append('https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_GENOMIC_REVOLUTION_MULTISECTOR_ETF_ARKG_HOLDINGS.csv')
# frames = []
# for url in url_list:
#     get_daily_update(url, frames, proxies=proxies)

# df = pd.concat(frames).reset_index(drop=True)

def get_cathie_woods():

	url_list = []
	url_list.append('https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_AUTONOMOUS_TECHNOLOGY_&_ROBOTICS_ETF_ARKQ_HOLDINGS.csv')
	url_list.append('https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_GENOMIC_REVOLUTION_MULTISECTOR_ETF_ARKG_HOLDINGS.csv')
	frames = []
	for url in url_list:

		get_daily_update(url, frames)

	df = pd.concat(frames).reset_index(drop=True)
	return df

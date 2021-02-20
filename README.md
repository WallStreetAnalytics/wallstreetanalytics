# stocks-testing
Stock analytics app (WIP)

Currently, the API gateway is the only functional code. 

# Using the API Gateway
To use the API gateway, first configure your API. Some APIs are already provided in the `/includes/functions/apis/` folder. 
If required, add your credentials to `/includes/functions/apis/{api name}/api.config.php`

Nasdaq API does not require credentials, since it is a public endpoint, so you can use that to test your installation. But you may wish to add custom options to your Guzzle requests, such as a proxy, which can be done in the API config file.

**Example Requests**

To fetch data from an API, POST to ./api/api.php with JSON. For example, to get SEC filings from Nasdaq.com for symbols GME & AAPL, you can make this request:

POST https://www.your.server/api/api.php
```
{
	"provider":"nasdaqapi", 
	"operation":"filings",
	"params":{
		"symbol":["GME","AAPL"],
		"limit":26,
		"sort": "companyName"
	}
}
```
 
POST https://www.your.server/api/api.php
If you have configured the fintel API, you can make the same request, but change the provider. 
```
{
	"provider":"fintelapi", 
	"operation":"filings",
	"params":{
		"symbol":["GME","AAPL"]
	}
}
```

**Notes**

Fintelapi does not offer as many refinements as the nasdaqapi provider, so you'll notice our request has less options. In this case, we could implement a result 'limit' in our PHP when processing the data from fintel. 

You'll also notice that the response from the API gateway is formatted as consistently as possible. The idea is to map responses to a common format so that the data can be ingested (either by the frontend code or a database) at a later time with minimal effort.

# What's left to do?

1. Front end:
- Templating
- Everything
2. Back End:
- Screeners / algorithms
- Add a request limiting middleware to Guzzle so we can obey QPS or QPM limits for certain APIs (reddit)
- Add more APIs to proxy
- Create a cron worker endpoint to enable data collection operations from finra, reddit, etc...
- Social sentiment analysis
- Allow a specific installation to be deployed as an API server, frontend, compute node
3. Other:
- Fix composer requirements files

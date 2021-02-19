#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from flask import Flask, render_template
import recipes


DEVELOPMENT_ENV  = True

app = Flask(__name__, static_url_path='/static')

app.config.from_pyfile('settings.py')


HTTP_PROXY = app.config.get("HTTP_PROXY")
HTTPS_PROXY = app.config.get("HTTPS_PROXY")

if HTTP_PROXY:
	proxies = {
	 'http': HTTP_PROXY,
	 'https': HTTPS_PROXY,
	}
else:
	proxies = None

@app.route('/')
def index():
	return render_template('index.html')


# @app.route('/Something_Cool')
# def about():
#     return render_template('about.html', app_data=app_data)


@app.route('/CathieWoods')
def CathieWoods():
	df = recipes.get_cathie_woods()
	df.set_index(['id'], inplace=True)
	df.index.name=None

	funds = list(set(list(df['fund'])))
	df_list = []
	for f in funds:
		next_df = df.loc[df.fund==f]
		df_list.append(next_df.to_html(classes='female'))

	# table_html = table_df.to_html()
	return render_template('CathieWoods.html', tables=[df.to_html(classes='mystyle')], titles=df.columns.values)

# @app.route('/contact')
# def contact():
#     return render_template('contact.html', app_data=app_data)


if __name__ == '__main__':
	app.run(host='0.0.0.0', port=8080)


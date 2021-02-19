#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from flask import Flask, render_template
import recipes


DEVELOPMENT_ENV  = True

app = Flask(__name__, static_url_path='/static')

@app.route('/')
def index():
    return render_template('index.html')


# @app.route('/Something_Cool')
# def about():
#     return render_template('about.html', app_data=app_data)


@app.route('/CathieWoods')
def service():
	df = recipes.get_cathie_woods()
	# table_html = table_df.to_html()
	return render_template('CathieWoods.html', tables=[df.to_html(classes='data')], titles=df.columns.values)

# @app.route('/contact')
# def contact():
#     return render_template('contact.html', app_data=app_data)


if __name__ == '__main__':
    app.run()


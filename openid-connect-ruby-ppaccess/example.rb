#
# Copyright 2013 PayPal
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#

require 'rubygems'
require 'bundler/setup'

require 'sinatra'
require './access.rb'

access = Access.new('YOUR_ID', 'YOUR_SECRET', 'http://myurl.com/auth')
access.set_scopes('openid+profile+email')

get '/' do
  redirect to(access.get_auth_url)
end

get '/rubyauth' do
  @code = params[:code]
  access.get_access_token(@code)
end 

get '/profile' do
  pretty(access.get_profile())
end

get '/refresh' do
  pretty(access.refresh_access_token())
end

get '/validate' do
  pretty(access.validate())
end

get '/logout' do
  pretty(access.logout())
end

def pretty(body)
  if body.nil?
  else
    puts JSON.pretty_generate(JSON.parse(body))
    "<pre>#{JSON.pretty_generate(JSON.parse(body))}</pre>"
  end
end

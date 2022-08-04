# Building Sites

While Bonfire focuses on providing a powerful backend area for your sites, a number of the provided tools can 
be used within your front-end sites with minimal work. This includes some helper functions for working with dates, 
a user consent framework for helping you comply with some of the global privacy laws, like GDPR, new tools to build
your views with, and more. 

Much like any CodeIgniter application, your code will typically live in the `/app` folder, utilizing the standard
`Controllers`, `Models`, `Views`, and other folders for your code. You can, of course, located modules anywhere you
wish as long as the autoloader can find it. None of this changes using Bonfire. All of Bonfire's code is kept 
in the `/bonfire` folder, where it will not conflict with your application's code. There are new config files to 
keep an eye out for, and Bonfire' `UserModel` resides in the `Models` for you to add your own functionality to. 

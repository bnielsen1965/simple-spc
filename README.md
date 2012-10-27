# Simple SPC statistical process control application

Simple SPC is a multiuser application that is designed to provide common statistical process control data collection, charts, and rules.
The application is still under early development and in an alpha version.

The multiuser aspect provides granular control over which application functions are available to each user based on the user's function.

The user functions include:
* Administrator
* Operator
* Technician
* Engineer

## Administrator

The administrator users will have access to the user adminstration page to create or modify existing user accounts.

## Operator

The operator users only have the ability to enter new data and view existing control charts.

## Technician

Not yet implemented. The technician user will have the ability to remove or edit data points in a control chart.

## Engineer

The engineer users can create new metrics and assign SPC rules to metrics.



# Rules

There are currently 8 supported Western Electric rules:

* WE1 = Any single data point falls outside the 3σ limit
* WE2 = Two out of three consecutive points fall beyond the 2σ limit on the same side of the centerline
* WE3 = Four out of five consecutive points fall outside 1 sigma on the same side of the centerline
* WE4 = Eight consecutive points fall on the same side of the centerline
* WE5 = Six points in a row increasing or decreasing
* WE6 = Fifteen points in a row within one sigma limits
* WE7 = Fourteen points in a row alternating in direction
* WE8 = Eight points in a row outside one sigma limits



# Demo site

A running demo of the application can be viewed at http://www.xorengineering.com/code/sspc/

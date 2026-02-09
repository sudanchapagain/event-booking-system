class diagram:

```uml
@startuml
left to right direction

class User {
  id
  name
  email
  password
  isUser
  isOrganizer
  isAdmin
  --
  login()
  signup()
  logout()
}

class Event {
  id
  title
  description
  location
  start_date
  end_date
  price
  capacity
  status
  --
  submitForApproval()
  approve()
  update()
  delete()
  reject()
}

class EventImage {
  id
  image_path
  image_type
}

class Booking {
  id
  quantity
  booking_date
  status
  --
  confirm()
  cancel()
}

class Payment {
  id
  amount
  method
  payment_status
  transaction_id
  --
  processPayment()
}

User "1" -- "0..*"
Event : creates User "1" -- "0..*"
Booking : makes Event "1" -- "0..*"
Booking : has Booking "0..1" -- "1"
Payment : payment Event "1" -- "0..*"
EventImage : images

@enduml
```

object diagram:

```uml
@startuml
left to right direction

object user1 {
  id = 1
  name = "John Doe"
  email = "john@example.com"
  isOrganizer = true
  isAdmin = false
}

object event1 {
  id = 101
  title = "Music Festival"
  location = "Kathmandu"
  price = 50
  capacity = 200
  status = "Approved"
}

object image1 {
  id = 201
  image_path = "event/banner.jpg"
  image_type = "banner"
}

object booking1 {
  id = 301
  quantity = 2
  booking_date = "2025-05-01"
  status = "Confirmed"
}

object payment1 {
  id = 401
  amount = 100
  method = "Online"
  payment_status = "Paid"
  transaction_id = "TXN12345"
}

user1 -- event1 : creates
user1 -- booking1 : makes
event1 -- booking1 : has
event1 -- image1 : images
booking1 -- payment1 : payment
@enduml
```

State diagram:

```uml
@startuml
left to right direction

[*] --> User_NotLoggedIn

state User_Module {
  User_NotLoggedIn --> User_LoggedIn : login/signup
  User_LoggedIn --> User_NotLoggedIn : logout
}

state Event_Module {
  Draft --> PendingApproval : submitForApproval()
  PendingApproval --> Approved : approve()
  PendingApproval --> Rejected : reject()
  Approved --> Updated : editEvent()
  Updated --> PendingApproval : resubmit()
}

state Booking_Module {
  Created --> PendingPayment : paidEvent()
  Created --> Confirmed : freeEvent()
  PendingPayment --> Confirmed : paymentSuccess()
  PendingPayment --> Cancelled : paymentFailed()
  Confirmed --> Cancelled : userCancels()
}

state Payment_Module {
  Payment_Pending --> Payment_Paid : processPaymentSuccess()
  Payment_Pending --> Payment_Failed : processPaymentFail()
}

User_LoggedIn --> Draft : organizerCreatesEvent
User_LoggedIn --> Created : userBooksEvent
PendingPayment --> Payment_Pending
Payment_Paid --> Confirmed
Payment_Failed --> Cancelled
@enduml
```

Sequence diagram:

```uml
@startuml
actor User
actor Admin

participant "Web Client" as Client
participant "Auth Controller" as Auth
participant "Event Controller" as EventCtrl
participant "Booking Controller" as BookingCtrl
participant "Payment Service" as Payment
database DB

== Authentication ==
User -> Client : login/signup request
Client -> Auth : send credentials
Auth -> DB : validate user
DB --> Auth : user data
Auth --> Client : auth success (200 OK)

== Event Creation ==
User -> Client : create event
Client -> EventCtrl : submit event details
EventCtrl -> DB : save event (Draft)
DB --> EventCtrl : event saved
EventCtrl --> Client : event created

User -> Client : submit for approval
Client -> EventCtrl : submit approval request
EventCtrl -> DB : update event (Pending Approval)

Admin -> Client : review event
Client -> EventCtrl : approve event
EventCtrl -> DB : update event (Approved)

== Event Booking ==
User -> Client : book event
Client -> BookingCtrl : create booking
BookingCtrl -> DB : save booking (Created)

alt Paid event
  Client -> User : request payment details
  User -> Client : payment details
  Client -> Payment : initiate payment
  Payment -> DB : save payment
  Payment --> Client : payment success
  Client -> BookingCtrl : confirm booking
  BookingCtrl -> DB : update booking (Confirmed)
else Free event
  Client -> BookingCtrl : confirm booking
  BookingCtrl -> DB : update booking (Confirmed)
end

BookingCtrl --> Client : booking confirmation
Client --> User : display confirmation
@enduml
```

```mermaid
classDiagram
    class TeamController {
        +index()
        +create()
        +store(Request request)
        +show(Team team)
        +edit(Team team)
        +update(Request request, Team team)
        +destroy(Team team)
        -authorizeAdmin()
        -usersPageData()
    }

    class Team {
        +int id
        +int user_id
        +string team_name
        +create()
        +update()
        +delete()
        +load()
    }

    class User {
        +int user_id
        +string role_name
        +paginate()
        +get()
    }

    class AppNotification {
        +int id
        +int user_id
        +string type
        +string title
        +create()
    }

    TeamController --> Team : manages / queries
    TeamController --> User : authenticates / filters
    TeamController --> AppNotification : dispatches updates
    Team "1" --> "*" User : has members
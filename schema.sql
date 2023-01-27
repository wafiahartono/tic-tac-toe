create table players
(
    id   varchar(256) not null
        primary key,
    name varchar(50)  not null
);

create table matches
(
    id           int auto_increment
        primary key,
    `cross`      varchar(256)      not null,
    circle       varchar(256)      not null,
    round        tinyint default 1 not null,
    score_cross  tinyint default 0 not null,
    score_circle tinyint default 0 not null,
    score_draw   tinyint default 0 not null,
    constraint matches_circle_uindex
        unique (circle),
    constraint matches_cross_uindex
        unique (`cross`),
    constraint matches_players_id_fk_circle
        foreign key (circle) references players (id),
    constraint matches_players_id_fk_cross
        foreign key (`cross`) references players (id)
);

create table moves
(
    `match`  int     not null,
    round    int     not null,
    sequence int     not null,
    position tinyint not null,
    constraint moves_matches_id_fk
        foreign key (`match`) references matches (id)
);

create table queues
(
    player varchar(256)             not null,
    mark   enum ('CROSS', 'CIRCLE') null,
    constraint queues_players_id_fk
        unique (player),
    constraint queues_players_id_fk
        foreign key (player) references players (id)
);



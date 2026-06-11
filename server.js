const express = require('express');
const mysql = require('mysql2');

const app = express();

app.use(express.json());

app.use(function(req, res, next) {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Content-Type', 'application/json');
    next();
});

// db connection
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'mysql',
    database: 'gymnastics_db'
});

// error for connection
db.connect(function(err) {
    if (err) {
        console.log('Database connection failed:', err.message);
    } else {
        console.log('Connected to gymnastics_db database');
    }
});

app.get('/api/status', function(req, res) {
    res.json({
        status: 'running',
        message: 'Gymnastics API is online',
        timestamp: new Date().toISOString()
    });
});

// to get all gymnasts
app.get('/api/gymnasts', function(req, res) {
    const sql = `
        SELECT gymnasts.*, users.full_name, users.email 
        FROM gymnasts 
        JOIN users ON gymnasts.user_id = users.id
    `;

    db.query(sql, function(err, results) {
        if (err) {
            res.status(500).json({ error: 'Database error' });
        } else {
            res.json(results);
        }
    });
});

// just getting one gymnast
app.get('/api/gymnasts/:id', function(req, res) {
    const id = req.params.id;
    const sql = `
        SELECT gymnasts.*, users.full_name, users.email 
        FROM gymnasts 
        JOIN users ON gymnasts.user_id = users.id
        WHERE gymnasts.id = ?
    `;

    db.query(sql, [id], function(err, results) {
        if (err) {
            res.status(500).json({ error: 'Database error' });
        } else if (results.length === 0) {
            res.status(404).json({ error: 'Gymnast not found' });
        } else {
            res.json(results[0]);
        }
    });
});

// filter for program
app.get('/api/gymnasts/program/:program', function(req, res) {
    const program = req.params.program;
    const sql = `
        SELECT gymnasts.*, users.full_name, users.email 
        FROM gymnasts 
        JOIN users ON gymnasts.user_id = users.id
        WHERE gymnasts.training_program = ?
    `;

    db.query(sql, [program], function(err, results) {
        if (err) {
            res.status(500).json({ error: 'Database error' });
        } else {
            res.json(results);
        }
    });
});


app.listen(3000, function() {
    console.log('Gymnastics API running at http://localhost:3000');
    console.log('Available routes:');
    console.log('  GET /api/status');
    console.log('  GET /api/gymnasts');
    console.log('  GET /api/gymnasts/:id');
    console.log('  GET /api/gymnasts/program/:program');
});
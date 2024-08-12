#!/usr/bin/env python3.10
import os
from pymongo import MongoClient, errors
from pymongo.collection import Collection

class MongoDBHelper:
    def __init__(self):
        self.host = os.getenv('MDB_HOST')
        self.username = os.getenv('MDB_USER')
        self.password = os.getenv('MDB_PW')
        self.auth = os.getenv('MDB_AUTH')
        self.tlsPath = os.getenv('MDB_TLS')
        self.databaseName = os.getenv('MDB_DB')
        connection_string = f'mongodb://{self.username}:{self.password}@{self.host}/{self.auth}&{self.tlsPath}'
        self.client = MongoClient(connection_string)
        self.db = self.client[self.databaseName]

    def find_document_by_field(self, collection_name, field_name, field_value):
        collection = self.db[collection_name]
        filter = {field_name: field_value}
        document = collection.find_one(filter)
        if document:
            return {'success': True, 'code': 'M54ND7', 'message': 'Successfully found document by field', 'document': document}
        else:
            return {'success': False, 'code': '68CSZ1', 'message': 'Unable to find field in document'}

    def count_documents(self, collection_name, conditions=None):
        collection = self.db[collection_name]
        filter = conditions if conditions else {}
        count = collection.count_documents(filter)
        if count == 0:
            return {'success': False, 'code': 'LFGB29', 'message': 'No documents found matching the criteria', 'count': count}
        else:
            return {'success': True, 'code': '4J532N', 'message': 'Successfully counted documents with given conditions', 'count': count}

    def find_documents_by_match_ids(self, collection_name, field_name, match_ids, fields_to_retrieve=None, return_as_array=False):
        collection = self.db[collection_name]
        filter = {field_name: {'$in': match_ids}}
        projection = self.create_projection(fields_to_retrieve)
        cursor = collection.aggregate([
            {'$match': filter},
            {'$project': projection}
        ])
        documents = list(cursor)
        if documents:
            is_empty_info = any(not doc.get('info') for doc in documents)
            if is_empty_info:
                return {'success': True, 'code': 'D4NG3R', 'message': 'Documents found, but some have empty "info" key', 'documents': documents}
            else:
                if return_as_array:
                    documents = [dict(doc) for doc in documents]
                return {'success': True, 'code': 'MO34LAN', 'message': 'Successfully found documents by match IDs', 'documents': documents}
        else:
            return {'success': False, 'code': 'KB6DL0', 'message': 'Document content is empty'}

    def create_projection(self, fields_to_retrieve):
        projection = {}
        for field in fields_to_retrieve:
            field_parts = field.split('.')
            modified_field = '.'.join(part[1:] if part.startswith('$') else part for part in field_parts)
            projection[modified_field] = 1
        return projection

    def delete_document_by_field(self, collection_name, field_name, field_value):
        collection = self.db[collection_name]
        filter = {field_name: field_value}
        document_exists = self.find_document_by_field(collection_name, field_name, field_value)['success']
        if not document_exists:
            return {'success': False, 'code': 'DL4MN2', 'message': 'Unable to delete non-existent document'}
        result = collection.delete_one(filter)
        if result.deleted_count > 0:
            return {'success': True, 'code': 'BD8M4L', 'message': 'Document deleted successfully'}
        else:
            return {'success': False, 'code': 'D3NF12', 'message': 'Unable to delete document because of unknown reason'}

    def insert_document(self, collection_name, document):
        collection = self.db[collection_name]
        try:
            collection.insert_one(document)
            return {'success': True, 'code': '8AMZLM', 'message': 'Successfully inserted document into ' + collection_name}
        except errors.DuplicateKeyError:
            return {'success': False, 'code': 'MXZ4P5', 'message': 'Document with the same key already exists in ' + collection_name}
        except Exception as e:
            return {'success': False, 'code': 'MXZZLM', 'message': 'An error occurred: ' + str(e)}

    def get_document_field(self, collection_name, filter_field, filter_value, field_name=None):
        collection = self.db[collection_name]
        filter = {filter_field: filter_value}
        projection = {field_name: 1} if field_name else None
        document = collection.find_one(filter, projection)
        if document:
            if field_name:
                if field_name in document:
                    return {'success': True, 'code': 'VZDDEB', 'message': 'Successfully retrieved field value of document.', 'data': document[field_name]}
                else:
                    return {'success': False, 'code': '5QNYRM', 'message': 'An unknown error occurred with field value.'}
            else:
                return {'success': True, 'code': 'DM83BG', 'message': 'Successfully retrieved whole document.', 'data': document}
        else:
            return {'success': False, 'code': 'FMLYAW', 'message': 'Document not found or not identifiable.'}

    def add_element_to_document(self, collection_name, filter_field, filter_value, array_field, element_to_add):
        collection = self.db[collection_name]
        filter = {filter_field: filter_value}
        update = {'$set': {array_field: element_to_add}}
        result = collection.update_one(filter, update, upsert=True)
        if result.modified_count > 0 or result.upserted_id:
            return {'success': True, 'code': '8AMZLM', 'message': 'Successfully added or updated element in ' + collection_name}
        else:
            return {'success': False, 'code': 'CN4NA1', 'message': 'Getting document by newly added field was not successful.'}

    def get_player_by_summoner_id(self, summoner_id):
        return self.get_document_field('players', 'PlayerData.SumID', summoner_id)

    def get_player_by_riot_id(self, game_name, tag):
        collection = self.db['players']
        game_name_regex = {'$regex': f'^{game_name}$', '$options': 'i'}
        tag_regex = {'$regex': f'^{tag}$', '$options': 'i'}
        filter = {'PlayerData.GameName': game_name_regex, 'PlayerData.Tag': tag_regex}
        document = collection.find_one(filter)
        if document:
            return {'success': True, 'code': 'AM5A3', 'message': 'Successfully retrieved player document.', 'data': document}
        else:
            return {'success': False, 'code': 'POL4M', 'message': 'Player document not found.'}

    def get_player_by_puuid(self, puuid):
        return self.get_document_field('players', 'PlayerData.PUUID', puuid)

    def get_autosuggest_aggregate(self):
        collection = self.db['players']
        pipeline = [
            {'$project': {'PlayerData.GameName': 1, 'PlayerData.Tag': 1, 'PlayerData.Icon': 1, '_id': 0}},
            {'$sort': {'PlayerData.GameName': 1}}
        ]
        cursor = collection.aggregate(pipeline)
        result = {f"{doc['PlayerData']['GameName']}#{doc['PlayerData']['Tag']}": doc['PlayerData']['Icon'] for doc in cursor}
        if result:
            return {'success': True, 'code': 'DJF64L', 'message': 'Successfully retrieved and sorted PlayerData.', 'data': result}
        else:
            return {'success': False, 'code': '0DL3MU', 'message': 'No PlayerData found.'}

    def aggregate(self, collection_name, pipeline, options=None):
        collection = self.db[collection_name]
        cursor = collection.aggregate(pipeline, **(options or {}))
        documents = list(cursor)
        if documents:
            return documents
        else:
            return None
